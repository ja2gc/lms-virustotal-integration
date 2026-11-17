# LMS VirusTotal Integration Implementation Plan

> **For agentic workers:** Use `superpowers:executing-plans` for the single assigned task when available. Use subagent-driven development only when the user explicitly chooses it; a member's task is not authorization to execute the whole plan. Steps use checkboxes. This document plans later product work; no product feature is implemented by this foundation session.

**Goal:** Deliver a locally reproducible Doodle submission workflow with durable VirusTotal advisories, two working reusable-component consumers and measured assessment evidence.

**Architecture:** One Doodle application and CLI worker share SQLite and private file storage. Durable scan-attempt rows drive asynchronous work; a provider adapter normalizes results, while host code owns authorization and persistence.

**Tech stack:** PHP 8.3, PDO SQLite, cURL, existing PHP views/CSS/vanilla JavaScript. No extra application framework, broker or cloud platform.

**Spec:** [Architecture](architecture.md), [scope](project-scope.md), [data model](data-model.md), [integration contracts](integrations.md), [security](security.md). The coordinator approved the design baseline; numerical settings are proposed initial defaults to validate, not achieved metrics.

## Global constraints

- Localhost demonstration; bind the web server to `127.0.0.1`.
- Reuse Doodle source and preserve its original history/license.
- PHP 8.3 and SQLite are the tested target to establish; MySQL/cloud support is not assumed.
- Keep submission/grade state separate from processing state and the four Technical 3 advisory strings.
- Synthetic files and fictional users only; keys remain server-side.
- Proposed initial upload policy: PDF/TXT/DOCX, at most 10,485,760 bytes; validate MIME mappings before implementation is merged.
- Provider defaults: <=4 requests/minute and <=500/day across all operations; 30-second request timeout, 90-second lease, 60-second ordinary poll interval, persisted 30-minute polling deadline.
- Never auto-replay an uncertain upload/rescan POST. Never publish a stale attempt as the current file's advisory.
- Each behavioral task writes a focused failing check, demonstrates failure, implements the minimal change, reruns its checks and updates affected docs before a focused commit/PR.

## Review focus

1. A crash after provider acceptance but before local handle persistence must not replay scan start (P3).
2. A quota wait beyond the polling deadline must not silently extend it (P3).
3. A text-only update or rejected replacement must not lose the previous valid attachment or show the wrong advisory (P1/P4).
4. A direct static file URL must not bypass the authorized download action (P0/P5).
5. Changed component output or optional provider categories must affect both real consumers consistently (P2/P3).

## Dependency order

`P0 -> P1 -> {P2, P4, P5} -> P3 integration -> P6 -> P7`.

P3 can build its orchestration tests with the published provider fake after P1; merge its live adapter connection after P2. P4/P5 use the shared schema/status response contract while the real worker is under construction. Merge common seams before touching the same controller/helper concurrently. Assign people in team chat, not in this file.

## P0: Make the scoped Doodle baseline reproducible

**Files:** modify `app/core/Controller.php`, its rendering calls throughout `app/controllers/`, scoped teacher view links, `install.php`, `.gitignore`, `README.md`; create `router.local.php`, `tests/run.php`, `tests/BaselineTest.php`, `dependencies.md` and `config/scan.example.php`.

**Interfaces:** preserve public `*/view/{id}` actions; rename the protected rendering helper to `render(string $view, array $data = []): void` and update every internal `$this->view(...)` render call. Preserve `viewRaw`. Align teacher URLs/forms with existing `gradeSubmission(int $id)`. Define CLI-safe loading in P1 rather than requiring `index.php` from jobs.

- [ ] Write controller-load checks for all 26 files, plus baseline installation and grade-route checks. The runner must throw/report nonzero without relying on PHP `assert()` settings.
- [ ] Run `php tests/run.php --group=baseline`; observe the reproduced class conflict. Execute the installer in a disposable target to reproduce B07 before fixing bootstrap loading.
- [ ] Rename the common renderer and callers, repair grade links/forms and proven installer loading defects. Implement a safe loopback router that blocks hidden/config/DB/private/docs/evidence paths and serves intended public assets/actions only. Ignore local sessions/install/migration flags and every real secret/data file.
- [ ] Rerun baseline checks; verify login, enrollment, assignment view, save, teacher review/grading and student feedback through a browser. Test direct protected paths under the actual router command.
- [ ] Add exact local dependency/setup commands to README/release guide, retaining unverified limitations. Commit only this repair task, with evidence; no scanning feature is implied by a green baseline.

**Acceptance:** BT01/BT02 and basic ST03 pass; a second member can reproduce installation. Record additional scoped blockers rather than expanding into every upstream feature.

## P1: Establish revision, attempt and shared contract foundation

**Files:** create `database/migrations/001_scan_foundation.php`, `bin/migrate.php`, `app/scan/bootstrap.php`, `app/scan/FileScanProvider.php`, `app/scan/ScanSubmissionService.php`, `app/scan/ScanAttemptRepository.php`, `tests/ScanPersistenceTest.php`; modify web bootstrap and `StudentController.php` at the agreed service seam.

**Interfaces:** `ScanSubmissionService::saveFileRevision(int $submissionId, array $validatedUpload): int` accepts normalized keys `tmpPath`, `originalName`, `sizeBytes`, `mimeType` after PHP-upload validation; returns immutable revision ID. `ScanAttemptRepository::saveResult(int $attemptId, string $leaseToken, array $result): bool` guards lease ownership and preserves current-pointer invariants. Publish `FileScanProvider::getResult(string $analysisId, string $expectedSha256): array` exactly as specified. Host services receive PDO/private-root/clock explicitly; no session dependency in reusable component.

- [ ] Write failing tests for repeatable migrations, same hash across submissions, revision replacement, pointer ownership, one automatic attempt/revision, file-write and transaction failures, and text-only updates retaining the existing file.
- [ ] Run `php tests/run.php --group=persistence` and record the missing behavior failure.
- [ ] Implement forward migrations and runner, `scan_provider_state`, private immutable storage, transactional queued work, uniqueness and guarded persistence. Backfill scoped old files safely with count/hash evidence; validate actual upload errors/MIME/move result before success. Do not make provider calls.
- [ ] Rerun persistence tests; apply migrations twice on disposable DBs; verify no duplicates and old-file retention on a failed replacement. Update data-model/contract docs if practical details changed.
- [ ] Commit this shared seam and have its consumers reviewed before parallel dependent merges.

**Acceptance:** IT01/IT02 persistence portion and file-failure cases pass. Authenticated streaming is completed by P5 before release; no temporary public-storage workaround is acceptable.

## P2: Implement the reusable VirusTotal result adapter

**Files:** create `app/scan/VirusTotalAdapter.php`, `app/scan/CurlTransport.php`, `tests/ScanAdapterTest.php`, `tests/fixtures/virustotal/`; extend bootstrap explicitly.

**Interfaces:** implement P1's unchanged `FileScanProvider`; injected HTTP callable and four result fields follow `integrations.md`. Both analysis and item calls use the metered transport. Constructor receives server key and transport; no SQL/session/upload/retry loop.

- [ ] Write fixtures and failing checks for each CT01/CT02/CT03 case, nonnegative integer `retryAfter`, input rejection without HTTP, pending analyses, provider errors and file-identity mismatch.
- [ ] Run `php tests/run.php --group=adapter`; confirm missing/incorrect behavior fails.
- [ ] Implement fixed-origin verified TLS requests, ID encoding, strict required-schema checks, conservative normalized classification and sanitized failures. Additive provider fields are tolerated, required identities/counters are enforced.
- [ ] Rerun adapter tests without network/key. A new fixture/category must exercise the same adapter used by both callers; no mock classifier bypass.
- [ ] Publish tested component-consumption instructions/version and commit this task.

**Acceptance:** contract cases pass offline; raw provider shapes never escape as the public result. Recover/compare original Technical 3 PHP and AI artifact if available; record actual divergences.

## P3: Implement durable orchestration and the two real consumers

**Files:** create `app/scan/ScanWorker.php`, `app/scan/ScanProviderBudget.php`, `app/scan/AutomaticSubmissionResult.php`, `app/scan/TeacherRescanResult.php`, `bin/scan-worker.php`, `tests/ScanWorkerTest.php` and provider-start helper methods kept outside `FileScanProvider`.

**Interfaces:** `ScanWorker::runOnce(): bool` claims one due step. Both origin-specific consumers expose `process(int $attemptId, string $leaseToken): void` and use the same injected `FileScanProvider` plus P1 persistence. Start requests normalize to a new analysis ID; they do not change the reusable result interface. Entry point supports `--once`, fixture/live mode and graceful stop.

- [ ] Write failing tests for claim competition, bounded retries, rate budget including two-call result reads, auth pause, stored-handle restart, both real consumers, stale file/attempt results, start-in-flight crash and deadline-versus-quota behavior.
- [ ] Run `php tests/run.php --group=worker` against deterministic fake transport/clock; confirm failures before implementation.
- [ ] Implement lookup -> explicit fresh re-analysis or upload -> handle persistence -> poll/identity -> guarded result. Persist `start_in_flight` before POST; uncertain/crashed starts terminate Unresolved. Use due times, no adapter sleep; do not hold transactions during HTTP. Quota reservation tracks actual HTTP starts under persisted shared state.
- [ ] Rerun worker checks and crash/restart tests. Verify every VT request is counted; terminal/old results cannot replace current status. Measure queue recovery using fake delays, clearly labeled.
- [ ] Commit orchestration and update actual recovery/contract details in canonical docs.

**Acceptance:** CT04, IT03/IT04/IT05/IT06 and FT01-FT03 pass in fixtures. This task is substantial backend implementation, not coordination-only work.

## P4: Add student advisory status and minimal shared UI

**Files:** create `app/controllers/ScanController.php` status action, `app/views/scan/_status.php`, `public/js/scan-status.js`, `tests/ScanStatusTest.php`; modify student/assignment templates and shared styles only as needed.

**Interfaces:** `ScanController::status(int $submissionId): void` implements IP09's exact authorized JSON contract; P5 adds its rescan action after this common controller seam is merged. Reuse a single advisory formatter/partial.

- [ ] Write failing status/authorization tests: pending/retry/completed/Unresolved, no file -> not_applicable, replacement freshness, unknown stored status -> Unresolved, no raw provider/path fields.
- [ ] Run `php tests/run.php --group=status` and confirm failures.
- [ ] Implement scoped status route and minimal template additions; keep grade state separate. Add 15-second active-only polling with hidden-tab pause, backoff and terminal stop; never trigger provider requests from polling.
- [ ] Rerun status tests; check UI01 at narrow/wide widths, keyboard use, long names and failed refresh. Preserve entered submission data on validation errors.
- [ ] Commit UI/status feature with screenshots and exact scenario results; update development guide.

**Acceptance:** student workflow and ST01 status scope pass. No frontend framework or new navigation system.

## P5: Add teacher rescans, history and protected downloads

**Files:** create `app/scan/ScanAttemptService.php`, `app/controllers/SubmissionFileController.php`, `app/views/scan/_history.php`, `tests/ScanAccessTest.php`; add `rescan` to merged `ScanController.php`; modify scoped teacher views and replace legacy public attachment links in all scoped consumers.

**Interfaces:** `ScanAttemptService::requestRescan(int $submissionId, int $actorId, string $requestToken): int`; `ScanController::rescan(int $submissionId): void`; `SubmissionFileController::download(int $fileId): void`. Service performs server authorization and idempotent enqueue, not HTTP scans. P3 processes teacher origin through its second consumer.

- [ ] Write failing checks for owner/cross-course/admin scope, missing file, GET rescan rejection, invalid CSRF, duplicate token, authorized history and direct-path download bypass.
- [ ] Run `php tests/run.php --group=access`; confirm unauthorized cases fail before implementation.
- [ ] Implement authorized new-attempt requests, current-attempt pointer, limited history and teacher details. Stream only authorized private files as attachments; no caller-selected paths or public fallback. Reuse shared status partial.
- [ ] Rerun ST01/ST02/ST03 and teacher UI paths. Prove a rescan obtains a new analysis ID under the worker; retrieving the old ID is not a fresh scan.
- [ ] Commit feature plus access-control evidence and documentation updates.

**Acceptance:** both real call sites work; downloads and rescan actions enforce object ownership on the server.

## P6: Run integration, performance, reliability and security experiments

**Files:** extend focused tests; add `tests/load/` runner using existing available tooling, sanitized outputs under `evidence/`; update `validation.md`, `security.md` and `release.md`.

**Interfaces:** consume the built system as documented; do not modify another task's contract silently to make a test pass.

- [ ] Run `php tests/run.php` and scoped role/browser acceptance; record actual failures and fix root causes in the owning task/PR.
- [ ] Execute the load ramp defined in `validation.md` against fake provider responses; report environment, variable, raw metrics, degradation point/tested bound and failure mode.
- [ ] Execute timeout, error, malformed-body and worker-crash trials; measure recovery including operator intervention where required.
- [ ] Perform LIVE01 once with permitted synthetic content, verifying real handle and item/hash relationship. No provider load test or real malware. Record fixture-versus-live distinction.
- [ ] Restore a disposable matched DB/private-file backup and have another member follow setup. Commit sanitized evidence and updated conclusions only.

**Acceptance:** R01-R09 have linked real evidence or an explicit unresolved failure. No fabricated performance or success claims.

## P7: Reconcile final artifacts and rehearse

**Files:** update canonical docs and final evidence manifest; preserve source artifacts/transcripts only after privacy review and with actual provenance.

- [ ] Reconcile supplied T1/T4, original component/transcript and historical RMMM with the built release; list any still-missing sources honestly.
- [ ] Update all register rows to implemented controls/retries/protocols; record divergences and their reasons.
- [ ] Add actual code/test/evidence links to both case lessons and all trend decisions.
- [ ] Complete each risk's final status, prediction-versus-outcome and reviewer/date; complete actual AI-use log.
- [ ] Have each member demonstrate their feature and explain one failure path; rehearse panel setup from a clean local target. Verify source revision and release contents before submission.

**Acceptance:** all Parts A-F trace to the system as built. Remaining failures/risks are explicitly reported; final completion is not inferred from this planning document.
