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

Local HTTP is accepted only on loopback for this demo. Provider transport is always verified HTTPS. No queue, scan-start request, result or download boundary may bypass [security controls](engineering.md#boundary-assessment).

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

`ScanSubmissionService::saveFileRevision(int $submissionId, array $validatedUpload): int` returns the new revision ID after file storage and DB commit. `ScanAttemptService::requestRescan(int $submissionId, int $actorId, string $requestToken): array` authorizes and returns `attemptId` (integer), `created` (Boolean), and `processingState` (string). The latter describes the actual existing/new attempt, not an assumed queued state. `ScanWorker::runOnce(): bool` processes one due step and reports whether work was claimed. These are target contracts; implement and compatibility-test them before parallel consumers merge.

Status JSON v1 contains `contractVersion`, `submissionId`, nullable `fileId`, nullable `attemptId`, `processingState`, nullable `advisoryStatus`, `reason`, and nullable UTC `checkedAt`. IDs and contractVersion are integers. Do not send disk paths, API keys or raw provider payloads. `processingState=not_applicable` is allowed for text-only submissions; advisory remains null. Rescan returns HTTP 202 for newly queued work, or 200 for a repeated token returning the same attempt and its actual state; JSON contains `contractVersion`, `attemptId`, `created`, and `processingState`. It never starts another attempt merely because a request is repeated. Error JSON contains stable `error.code` and useful `error.message` only.

### Shared host repository contract

**P1a implementation handoff:** Member 4 owns scan schema/repository/bootstrap; Member 1 owns P1b upload storage, submission integration and legacy backfill. Current publication/readiness is recorded in the team plan. Requiring `app/scan/bootstrap.php` loads classes only: no configuration, sessions, database connection, migration or processing side effect. Run `php bin/migrate.php` explicitly against an installed SQLite baseline; it is repeatable and does not import legacy files. MySQL scan migrations are not supported by the tested runtime.

Local configuration: entry points require `config/config.php`, which loads the ignored root `.env`. Member 2 reads `getenv('VT_API_KEY')` and `getenv('SCAN_MODE')` when constructing the adapter/worker; do not add a second dotenv parser or expose the key through an endpoint. Existing process values win. Offline tests force fixture mode and do not inherit the developer's API key. README owns the setup instructions; configuring a key does not certify live processing.

`new ScanAttemptRepository(PDO $db, ?callable $clock = null)` accepts a clock returning exact UTC `YYYY-MM-DDTHH:MM:SSZ` strings. All injected `now`, lease and deadline strings use that format. The constructor enables SQLite foreign keys and a five-second busy timeout. No method performs HTTP or file writes.

Member 1 registers bytes through `registerFileRevision(int $submissionId, array $file): int`. Keys are `storageKey` (32–64 lowercase hexadecimal characters, basename only), `originalName` (1–255 bytes, no control characters), `sha256` (64 lowercase hexadecimal characters), `sizeBytes` (nonnegative integer), `mimeType` (MIME string). It returns the immutable file ID and atomically creates one automatic attempt and both current pointers. Duplicate hashes remain distinct. Write/validate immutable bytes first; remove only the newly written file if the transaction fails. For an atomic academic save, begin a transaction with the same PDO, create/update the submission, register the revision, then commit; repository savepoints preserve the caller's transaction. An outer rollback removes revision/queue/pointer changes. File rollback and upload validation remain Member 1's responsibility.

The P1a migration adds `scan_schema_versions` (version 1), `submission_files`, `scan_attempts`, `submissions.current_file_id`, and singleton `scan_provider_state`. The provider-state row uses `id=1`, `request_timestamps_json` (default `[]`), `reservations_json` (default `[]`), nullable `paused_until`, and `updated_at` UTC. Member 2 owns budget logic and publishes the JSON entry shapes with that implementation; no credentials belong in these columns. Changes to physical schema require a new forward migration coordinated with Member 4.

Claim and registration methods use short SQLite write transactions. Result/progress writes require the current nonexpired lease and running state; stale writes return false. Uncertain expired starts fail without replay. Overdue inactive polling work terminates Unresolved. These are repository checks, not an implemented worker. Context/history are internal records; HTTP consumers authorize and explicitly project public fields.

These methods belong to Member 4's P1a `ScanAttemptRepository`. Connected consumers use the published, verified P1a contract. P1b remains required for real uploads. Other streams request contract changes rather than creating parallel table writers.

| Method | Consumer / required semantics |
| --- | --- |
| `claimDue(string $leaseToken, string $nowUtc, string $leaseUntilUtc): ?array` | Worker; atomically claim one eligible step, null if none. Expired uncertain starts are failed conservatively, never selected for replay. |
| `saveProgress(int $attemptId, string $leaseToken, array $changes): bool` | Worker; lease-guarded allowlisted step/state/scheduling changes. False means claim no longer owned. |
| `saveResult(int $attemptId, string $leaseToken, array $result, array $transition): bool` | Both result consumers; normalized result and resulting lifecycle/schedule are stored in one guarded transaction. No half-updated advisory is published. |
| `getSubmissionContext(int $submissionId): ?array` | HTTP service; internal current projection plus student/course/teacher ownership IDs. Caller authorizes before disclosure. |
| `getFileContext(int $fileId): ?array` | Download service; internal file metadata/storage key and ownership IDs. Never expose this whole object to the browser. |
| `getAttemptHistory(int $fileId): array` | Already-authorized teacher review; normalized attempt records newest first. |
| `requestRescan(int $submissionId, int $actorId, string $requestToken): array` | Authorized host service; transactionally reuse a token or create an attempt/current pointer. Return `attemptId`, `created`, `processingState` as defined above. Repository enforces row/token consistency; service enforces authorization. |

Claim rows use camelCase keys: `attemptId`, `fileId`, `submissionId`, `origin`, `state`, `step`, nullable `analysisId`, `sha256`, `storageKey`, `leaseToken`, nullable `pollDeadlineAt`, Boolean `startInFlight`, nullable `nextRunAt`, and integer `failureCount`. Map SQL snake_case inside the repository. `saveProgress` allows only `state`, `step`, `analysisId`, `startInFlight`, `pollDeadlineAt`, `nextRunAt`, `failureCount`, `reason`, and `leaseUntil`; identity/current pointers are not arbitrary update fields.

`saveResult` takes the unchanged four-field component result plus optional component fields. Its explicit host `transition` has `state` (waiting, completed or failed), nullable `nextRunAt`, nonnegative `failureCount`, and nullable `completedAt`. Terminal states require completedAt and null nextRunAt; waiting requires nextRunAt and null completedAt. Only completed, validated, non-retryable evidence can publish a favorable advisory. The host chooses scheduling; the repository validates consistency and writes atomically. Invalid input throws a validation exception; false is reserved for stale/missing claim ownership.

Submission context contains IP09 fields plus `studentId`, `courseId` and `teacherId`. File context contains `fileId`, `submissionId`, `studentId`, `courseId`, `teacherId`, `storageKey`, `originalName`, `mimeType` and `sizeBytes`. History records contain attempt ID/origin/state/advisory/reason/counts and requested/completed UTC times; no raw provider payload. Never use ownership fields supplied by the browser in place of these authoritative joins.

### Shared pending-response example

**P5 consumer handoff:** `GET scan/status/{submissionId}` returns the fields below. A permitted owning teacher/admin also receives optional `rescanToken`, issued for that submission in the current browser session. Students do not receive it. After an operation uses a token, the next status read provides a new one; old issued tokens remain valid for same-session replay. Tokens are not CSRF tokens and must not be logged.

Member 3 submits `POST scan/rescan/{submissionId}` with form fields `_csrf_token` (existing CSRF token) and `requestToken` (the issued rescanToken). New work returns 202 and `created=true`; token replay returns 200, the same attempt ID and its actual stored processing state. GET returns 405; CSRF failure 419; missing session 401; wrong owner/course/role 403; missing submission 404; invalid/unissued request token or no file 422. All endpoint errors use `error.code` and `error.message`. No HTTP action calls a provider. End-to-end fresh-analysis proof remains P3 integration work.

`GET scan/history/{submissionId}` is teacher/admin-only under the same course ownership rule. It returns `{contractVersion, submissionId, fileId, history}`. History entries contain `attemptId`, `origin`, `processingState`, nullable `advisoryStatus`, `reason`, nullable validated `stats`, `requestedAt` and nullable `completedAt`. It contains the current file revision's attempts, newest first. Empty/no-file history is `[]`. Unknown status values display Unresolved, not favorable evidence.

Member 3 builds attachment links with **fileId**, not submissionId: `submissionFile/download/{fileId}`. The endpoint permits owner/course-teacher/admin, streams attachment/octet-stream with no-store/nosniff, and returns 404 for missing or size-mismatched bytes. Member 1 writes immutable bytes directly under `SCAN_PRIVATE_DIR` using the repository `storageKey` as the basename. Default root: `ROOT_DIR/private/submissions`; that directory is ignored and denied by the localhost router. An external private root is also supported. A root inside `public` is rejected. Links/symlinks that escape the configured root are denied. Do not append an extension to the storage key or build a caller-selected path.

The localhost router denies old `public/uploads/submissions` URLs, including resolved Windows short-name aliases. Original files are preserved on disk; legacy attachment links remain unavailable until Member 1 imports them and Member 3 switches links to file IDs. This is an explicit integration gate, not completed backfill or attachment acceptance. Course lesson uploads are a separate retained workflow. The supported/verified web command is the README localhost router; alternate web-server deployment is not claimed.

```json
{
  "contractVersion": 1,
  "submissionId": 101,
  "fileId": 7,
  "attemptId": 42,
  "processingState": "queued",
  "advisoryStatus": null,
  "reason": "scan_queued",
  "checkedAt": null
}
```

`scan_queued` is a host reason, not a provider finding. UI, HTTP and worker teams use the same synthetic examples. Also test every advisory, Unresolved failure and text-only not_applicable response.

### Semantics gate

| Value | Exact meaning |
| --- | --- |
| Submission ID | One student's academic submission to an assignment |
| File ID | Immutable attachment revision; equal hashes do not imply equal file IDs |
| Attempt ID | One automatic or teacher-requested operation on that revision |
| Analysis ID | External handle returned by a newly requested provider analysis; not a file hash |
| processingState | Host lifecycle, independent of grade and advisory |
| advisoryStatus | Exact Technical 3 normalized label; null before an advisory exists; only the component classifies evidence |
| SHA-256 | 64 lowercase hexadecimal characters, checked against exact file bytes |
| Time / size | UTC timestamps; durations/retry hints in seconds; file sizes in bytes |

Written contracts reduce ambiguity but do not prove runtime interoperability. Handoff requires shared fixture compatibility, the complete submission-to-result vertical slice, and limited real provider evidence. No API integration has been executed by this planning session.

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
