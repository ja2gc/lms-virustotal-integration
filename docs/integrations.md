# Integration register and contracts

Target contracts, not implemented endpoints. `index.php?r=...` matches Doodle's existing router style; do not invent a separate `/api/v1` gateway for this project. Current session login is reused for the scoped browser routes. Public routing must use an explicit safe action set and correct HTTP methods.

## Integration-point register

Every target boundary is listed, including internal ones. Browser authentication is insufficient by itself: enforce submission ownership or course-teacher/admin scope on every object.

| ID / boundary | Protocol, format, auth | Timing | Errors and retries | Versioning |
| --- | --- | --- | --- | --- |
| IP01 browser -> submission action | Local HTTP; multipart form plus CSRF and session; student enrollment checked | Synchronous local save; asynchronous scan | Validation errors preserve input; failed file move/DB commit rejects save; no automatic browser POST replay | Existing `student/submitAssignment/{assignmentId}`; additive project fields |
| IP02 web -> durable work | PDO/SQLite transaction; typed file/attempt rows; local OS/DB access | Enqueue in submission/rescan transaction | Roll back incomplete row changes; conditional uniqueness prevents duplicate automatic work | Forward migration version; attempt contract version 1 |
| IP03 worker -> VT hash lookup | HTTPS `GET /api/v3/files/{sha256}`; JSON; server `x-apikey` | Worker request | 404 selects upload; report is not treated as a fresh result; transient errors scheduled, never favorable | VT v3; validate required fields, tolerate additional fields |
| IP04 worker -> VT upload | HTTPS `POST /api/v3/files`; multipart file, JSON analysis descriptor; server API key | Request returns handle; analysis is async | Validate descriptor; ambiguous timeout -> Unresolved/start_outcome_unknown, no blind automatic retry | VT v3; descriptor fixture |
| IP05 worker -> VT fresh rescan | HTTPS `POST /api/v3/files/{sha256}/analyse`; JSON descriptor; server API key | Same async lifecycle | Used for known file hashes; a new ID must be returned; ambiguous timeout handled as IP04 | VT v3; verify account access in live smoke check |
| IP06 component -> VT result | HTTPS `GET /api/v3/analyses/{analysisId}`; JSON; server API key | Scheduled polling | Pending schedules next poll; validate status, identity, stats; bounded read retries | `FileScanProvider` v1 and VT v3 |
| IP07 component -> VT file identity | HTTPS `GET /api/v3/analyses/{analysisId}/item`; JSON file; server API key | Additional read when needed | Require returned file SHA-256 to match saved hash; absence/mismatch -> Unresolved; transient read retry only | VT v3 item relationship; fixture and live check |
| IP08 worker -> results | PDO/SQLite; normalized fields/counts; local worker access | Short transaction after HTTP | Lease-token conditional write; bounded DB contention retry; stale workers cannot publish current advisory | Forward schema migration plus component contract version |
| IP09 browser -> status | Local HTTP `GET index.php?r=scan/status/{submissionId}`; JSON; session plus object authorization | Synchronous read; browser polls while active | 401/403/404 don't retry automatically; transient failure preserves last display with error; unknown status displays Unresolved | Project contract v1; additive optional fields |
| IP10 teacher -> rescan | Local HTTP `POST index.php?r=scan/rescan/{submissionId}`; CSRF, session, course ownership; server-issued idempotency token | Synchronous enqueue then async work | No file -> validation error; duplicate token returns original attempt; no external call in controller | Project contract v1 |
| IP11 browser -> attachment | Local HTTP `GET index.php?r=submissionFile/download/{fileId}`; session, owner/course authorization; streamed bytes | Synchronous authorized transfer | Deny unauthorized access before opening path; missing disk file -> 404; no static fallback | Existing legacy links migrated to this route |

Local HTTP is accepted only on loopback for this demo. Provider transport is always verified HTTPS. No queue, scan-start request, result or download boundary may bypass [security controls](security.md).

## Published reusable component

Retain the Technical 3 interface, separate from its implementation:

```php
interface FileScanProvider
{
    public function getResult(string $analysisId, string $expectedSha256): array;
}
```

Proposed files: `app/scan/FileScanProvider.php` and `app/scan/VirusTotalAdapter.php`. The host must explicitly load the `app/scan/` bootstrap in web and CLI entry points; the current autoloader does not discover that directory. No framework migration is required.

Inputs: analysis ID is 1-512 printable ASCII bytes without whitespace; SHA-256 is exactly 64 hexadecimal characters, normalized lowercase. URL-encode the ID as one path segment. Invalid inputs make no network request.

Required result fields:

| Field | Type / meaning |
| --- | --- |
| `status` | Exactly `Flagged`, `Review Recommended`, `No Detections Reported`, or `Unresolved` |
| `reason` | Stable nonempty machine code; never unsanitized provider error text |
| `retryable` | Boolean; whether another scheduled result-read could help |
| `retryAfter` | Null or nonnegative integer seconds; scheduling hint only |

Optional additive fields: `stats` with validated counters; `observedAt` as an ISO-8601 UTC timestamp; `analysisId` for diagnostic correlation. Required fields and status meanings are stable in component version 1.0.0. Patch releases preserve behavior; compatible optional fields are minor; required-input/field/status-meaning changes require a major version and consumer migration.

The adapter is a named **Adapter pattern**: provider HTTP/JSON becomes an application-owned result. It does not authorize, query SQL, upload files, initiate scans, retry, sleep, enqueue jobs or select which attempt is current. Construction accepts one injected HTTP callable plus a server-held API key. Transport contract: `(string $method, string $url, array $headers, ?array $multipart): array`, returning `httpStatus` (integer, 0 on transport failure), `headers` (normalized lower-case names), `body` (string), and `transportError` (null or normalized reason). Fixtures replace that transport, not the classification logic.

The adapter issues at most one analysis read and, for completed usable evidence, one identity read per call. The worker reserves the possible two-call quota budget before invoking it and the transport enforces each request reservation. Unused reservations are released. Never hide unmetered HTTP inside a helper.

### Classification policy

1. Validate `data.type=analysis` and matching returned analysis ID. Pending (`queued` / `in-progress`) returns Unresolved with `analysis_pending`, retryable true.
2. For `completed`, require a matching file item and valid nonnegative integer counters for all eight documented file-analysis categories: malicious, suspicious, harmless, undetected, timeout, confirmed-timeout, failure, type-unsupported. Missing/invalid counters remain Unresolved. Do not apply a URL-analysis example's smaller schema to file analyses.
3. Malicious > 0 -> Flagged; otherwise suspicious > 0 -> Review Recommended.
4. Otherwise, No Detections Reported requires harmless + undetected > 0 and zero timeout, confirmed-timeout, failure and type-unsupported counts. Other outcomes are Unresolved. This deliberately conservative Technical 3 policy may leave many analyses unresolved; display the reason and counts.
5. File mismatch, malformed JSON/schema, empty results, or unsupported upstream statuses cannot produce a favorable advisory. Never show `safe`, `clean` or `virus-free` as the application's conclusion.

Reasons include `malicious_detected`, `suspicious_detected`, `no_detections_reported`, `analysis_pending`, `inconclusive_engines`, `invalid_input`, `malformed_response`, `analysis_mismatch`, `file_mismatch`, `identity_unavailable`, `provider_timeout`, `provider_unavailable`, `provider_auth_failed`, `quota_deferred`, and `start_outcome_unknown` (host orchestration). Incomplete evidence remains visible, not silently collapsed into an empty badge.

The host also uses `polling_exhausted` for the persisted wall-clock deadline. Before upload or re-analysis POST, persist `start_in_flight=true`. Atomically store the returned analysis ID, clear the marker and set `poll_deadline_at` when the request succeeds. Expired in-flight starts without an ID fail conservatively as `start_outcome_unknown`; a worker restart must not replay them.

### Two consumers

`AutomaticSubmissionResult::process(int $attemptId, string $leaseToken): void` and `TeacherRescanResult::process(int $attemptId, string $leaseToken): void` are host use cases selected by attempt origin. Each loads its durable revision/analysis identity, calls the same injected `FileScanProvider::getResult(...)`, then passes the normalized record to the same guarded persistence method. Neither reimplements classification. They are real execution paths, not two unused wrapper demos. Shared orchestration/persistence is reused; only origin/authorization audit context differs.

### Host orchestration

`ScanSubmissionService::saveFileRevision(int $submissionId, array $validatedUpload): int` returns the new revision ID after file storage and DB commit; `ScanAttemptService::requestRescan(int $submissionId, int $actorId, string $requestToken): int` returns an existing/new attempt ID after authorization. A `ScanWorker::runOnce(): bool` claim processes one due step and reports whether work was claimed. These are proposed public host signatures; refine in the first contract PR before parallel implementation.

Status JSON v1 contains `contractVersion`, `submissionId`, nullable `fileId`, nullable `attemptId`, `processingState`, nullable `advisoryStatus`, `reason`, and nullable UTC `checkedAt`. Do not send disk paths, API keys or raw provider payloads. `processingState=not_applicable` is allowed for text-only submissions; advisory remains null. Rescan returns 202 with `attemptId` and `processingState=queued`, or the prior attempt for the same token. Error JSON contains stable `error.code` and useful `error.message` only.

## Scheduling defaults and capacity

Proposed initial settings, to be configured through an example template and tested: one worker; 30-second HTTP timeout; 90-second claim lease; 60 seconds between ordinary polls; at most 30 minutes from obtaining an analysis ID to terminal polling exhaustion. Each retryable failed read gets up to five retries at 30, 60, 120, 240 and 480 seconds plus 0-20% jitter. Successful pending polls do not consume the transient-failure counter. Defer to the later of backoff, provider Retry-After and quota availability; daily quota delay is not a rapid retry loop.

Persist the deadline when saving the analysis ID. Check it before each next provider request and after quota deferral; quota waits do not extend it. Expired attempts terminate Unresolved without more calls. A quota-deferred attempt without an analysis ID stays queued/waiting until capacity or operator cancellation; no completion-time guarantee is made.

Use a shared SQLite `scan_provider_state` singleton (request timestamps/counters and `paused_until`, no key) with short atomic reservations. Limit all HTTP calls together to the account's configured allowance, initially no more than 4/minute and 500/day. The team uses one account budget, not multiple accounts to evade limits. Any activity outside this app can still consume that account's allowance. Distinguish `TooManyRequestsError` from `QuotaExceededError`; if exhausted-window information is unknown, defer conservatively for operator review. Authentication/authorization errors pause provider work and require operator correction; submissions continue saving.

No blind replay of upload/rescan POSTs after an unknown outcome. Store diagnostic failure; an authorized explicit new attempt may create duplicate provider work and must be presented as such. Reads may be retried. Reuse stored analysis IDs across worker restarts. Request count, polls, queue delay and provider completion time determine capacity; do not promise a fixed number of submissions per day.

## Official references

- [File analysis object and categories](https://docs.virustotal.com/reference/analyses-object)
- [Analysis item relationship](https://docs.virustotal.com/reference/analysis-object-item)
- [File upload, size limit and data-sharing warning](https://docs.virustotal.com/reference/files-scan)
- [Fresh re-analysis](https://docs.virustotal.com/reference/files-analyse)
- [Public API limits and usage restrictions](https://docs.virustotal.com/reference/public-vs-premium-api)
- [HTTP and provider error codes](https://docs.virustotal.com/reference/errors)

These references establish the documented API, not the group's account capability or a successful live request. Verify the chosen endpoints and identity relationship with the actual permitted account before claiming the implementation is complete.
