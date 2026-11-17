# LMS VirusTotal Integration Implementation Plan

> **For agentic workers:** Use `superpowers:executing-plans` for the single assigned task when available. Use subagent-driven development only when the user explicitly chooses it; a member's task is not authorization to execute the whole plan. Steps use checkboxes. This document plans later product work; no product feature is implemented by this foundation session.

**Goal:** Deliver a locally reproducible Doodle submission workflow with durable VirusTotal advisories, two working reusable-component consumers and measured assessment evidence.

**Architecture:** One Doodle application and CLI worker share SQLite and private file storage. Durable scan-attempt rows drive asynchronous work; a provider adapter normalizes results, while host code owns authorization and persistence.

**Tech stack:** PHP 8.3, PDO SQLite, cURL, existing PHP views/CSS/vanilla JavaScript. No extra application framework, broker or cloud platform.

**Spec:** [Software Requirements Specification](SRS.md) first; then [architecture](architecture.md), [data model](data-model.md), [integration contracts](integrations.md) and [security](security.md). FR/NFR IDs define required behavior; R01-R15 define the assessment mapping. Numerical targets explicitly marked proposed remain hypotheses to validate, not achieved metrics.

## Global constraints

- `docs/SRS.md` is the required-behavior baseline. Map changes to its FR/NFR/R IDs and relevant manual/automated acceptance; the implementation plan does not override the SRS.

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

`P0a -> {P0b, P1}; P1 -> {P2, P3 with fixtures}; {P0b, P1} -> P5; {P2, P1} -> connected P3; {P3, P5} -> connected P4; integrated P3/P4/P5 -> P6 -> P7`.

P0b and P1 can proceed in parallel after P0a because they own different files. Isolated provider fixtures/UI partials/route planning can start immediately. See [the team guide](team-guide.md) for exactly when to wait and [the demo manual](demo-manual.md) for the shared acceptance scenarios.

P3 can build its orchestration tests with the published provider fake after P1; merge its live adapter connection after P2. P4/P5 use the shared schema/status response contract while the real worker is under construction. Merge common seams before touching the same controller/helper concurrently. Assign people in team chat, not in this file.

## Workstream boundaries and safe handoffs

These are task/file boundaries, not assignments to named members or contribution-size targets. Map the four workstreams to classmates in team communication.

| Stream | Primary ownership | Requests to other streams |
| --- | --- | --- |
| Foundation and data | P0a; P1; submission/revision/attempt migrations and repository; submission service; `StudentController.php`; initial CLI/web scan bootstrap seam | Provider/HTTP streams request new repository methods here rather than editing SQL/migrations concurrently |
| Scanning engine | P2/P3; adapter, transport, provider budget, worker, both result consumers, CLI worker and provider fixtures | Uses frozen repository methods; does not edit student/teacher templates or HTTP controllers |
| LMS interface | P4; all student/teacher scan templates including history; shared header/navigation, dashboards, CSS/JS and label formatter | Requests backend changes through the HTTP stream; does not duplicate authorization or provider classification |
| HTTP, access and local delivery | P0b; P5; Router/action map, local server router, scan/status/rescan/download controllers, authorization, rescan service and local setup evidence | Uses data repository; does not write submission/scan SQL or edit templates owned by UI |

P0a's mechanical rendering sweep touches existing controllers and some grade links once, **before** feature branches edit them. After that merge, hand those files to their listed stream. P0b changes routing in its own PR while P1 publishes bootstrap/schema/test seams in a separate PR. Do not work concurrently on the same file merely because changes seem small.

The shared test runner from P0a discovers `tests/*Test.php`. Each test file returns `['group' => '<group>', 'tests' => ['test name' => callable]]`; supported groups are baseline, routing, persistence, adapter, worker, ui, status and access. The runner supports `--group=<group>` and exits nonzero on failure. Other streams add their own test files without editing this runner. This is a planned convention, not an existing harness.

**Handoff package for every PR:** source revision/branch; changed files; exact provided/consumed signatures or JSON example; migration/config effects; focused test command and real result; sanitized fixture/evidence locations; known limitations; affected downstream task. Review and merge that package before a consumer depends on it.

**Contract gate:** P1 publishes the repository/interface below; P2 publishes provider fixtures; P5 publishes status/rescan examples; P4 consumes those exact examples. Contract changes need the producer and consumer to agree and update docs/tests together. Never independently rename a key, status or time unit. A complete vertical slice (saved file -> durable attempt -> adapter -> validated result -> authorized UI) must pass before final evidence/rehearsal.

All streams supply tests, documentation and evidence for their own work. P6/P7 are shared integration/release gates; coordinating evidence does not transfer everyone's testing to one person.

## P0: Make the scoped Doodle baseline reproducible

**P0a files (foundation first):** modify `app/core/Controller.php`, its rendering calls throughout `app/controllers/`, scoped teacher view links, `install.php`, `.gitignore`, `README.md`; create `tests/run.php`, `tests/BaselineTest.php`, `dependencies.md` and `config/scan.example.php`.

**P0b files (HTTP/access after P0a merge):** modify `app/core/Router.php`; create `router.local.php`, `config/routes.php` and `tests/RoutingTest.php`. The explicit action map retains the approved features and denies excluded controllers **and** quiz actions within retained controllers, rather than merely hiding links.

**Interfaces:** preserve public `*/view/{id}` actions; rename the protected rendering helper to `render(string $view, array $data = []): void` and update every internal `$this->view(...)` render call. Preserve `viewRaw`. Align teacher URLs/forms with existing `gradeSubmission(int $id)`. Define CLI-safe loading in P1 rather than requiring `index.php` from jobs.

- [ ] Write controller-load checks for all 26 files, plus baseline installation and grade-route checks. The runner follows the discovery contract above and must throw/report nonzero without relying on PHP `assert()` settings.
- [ ] Run `php tests/run.php --group=baseline`; observe the reproduced class conflict. Execute the installer in a disposable target to reproduce B07 before fixing bootstrap loading.
- [ ] In P0a rename the common renderer/callers and repair grade links/forms and proven installer defects; ignore local sessions/install/migration flags and secrets/data. Verify and merge this baseline sweep before other streams edit controllers/templates.
- [ ] In P0b write failing routing tests, then implement the safe loopback router and action map: block hidden/config/DB/private/docs/evidence paths; deny excluded modules/actions; preserve approved course/material/calendar/announcement/notification/profile/grading paths. Run `php tests/run.php --group=routing` plus direct-URL checks before merging.
- [ ] Rerun baseline checks; verify login, enrollment, assignment view, save, teacher review/grading and student feedback through a browser. Test direct protected paths under the actual router command.
- [ ] Add exact local dependency/setup commands to README/release guide, retaining unverified limitations. Commit only this repair task, with evidence; no scanning feature is implied by a green baseline.

**Acceptance:** BT01/BT02, UI02's retained-route smoke check and ST03/ST04 pass; a second member can reproduce installation. No quiz page/action is included in the target interface. Record additional scoped blockers rather than expanding into every upstream feature.

## P1: Establish revision, attempt and shared contract foundation

**Files:** create `database/migrations/001_scan_foundation.php`, `bin/migrate.php`, `app/scan/bootstrap.php`, `app/scan/FileScanProvider.php`, `app/scan/ScanSubmissionService.php`, `app/scan/ScanAttemptRepository.php`, `tests/ScanPersistenceTest.php`; modify web bootstrap and `StudentController.php` at the agreed service seam.

**Interfaces:** `ScanSubmissionService::saveFileRevision(int $submissionId, array $validatedUpload): int` accepts normalized keys `tmpPath`, `originalName`, `sizeBytes`, `mimeType` after PHP-upload validation; returns immutable revision ID. `ScanAttemptRepository::saveResult(int $attemptId, string $leaseToken, array $result, array $transition): bool` guards lease ownership and persists result/lifecycle atomically. Publish `FileScanProvider::getResult(string $analysisId, string $expectedSha256): array` exactly as specified. Host services receive PDO/private-root/clock explicitly; no session dependency in reusable component.

Freeze the additional repository contract in [integrations](integrations.md#shared-host-repository-contract) before handing off to scanning/HTTP work. The foundation stream owns submission/revision/attempt SQL; the scanning stream owns request-budget access to `scan_provider_state`. Do not create a second repository/parallel migration to resolve a consumer mismatch.

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

**Acceptance:** CT04, IT03/IT04/IT05/IT06 and FT01-FT03 pass in fixtures. Provider and worker orchestration have one stream owner; contribution size is not an acceptance criterion.

## P4: Add the assignment-focused LMS interface and scan views

**Files:** create `app/views/scan/_status.php`, `app/views/scan/_history.php`, `public/js/scan-status.js`, `tests/ScanViewTest.php`, and UI fixtures under `tests/fixtures/status/`; modify retained student/assignment/teacher templates, shared header/navigation, dashboards, `app/helpers/functions.php` presentation helpers and styles as needed. No HTTP controller is owned by this task.

**Interfaces:** consume IP09 status JSON and P5's rescan/download routes exactly as published; use fixture examples while endpoints are built. Own both student and teacher presentation to prevent competing edits to shared partials. Reuse one advisory formatter; never reclassify provider counts in JavaScript.

- [ ] Write failing view tests for pending/retry/completed/Unresolved, text-only not_applicable, replacement freshness, escaped long filenames and preserved form fields. Include approved menu destinations and absence of quiz/excluded links.
- [ ] Run `php tests/run.php --group=ui` and confirm failures.
- [ ] Implement student and teacher views including small scan history, grade/feedback and rescan form consuming P5 endpoints. Retain the approved course/material/calendar/announcement/notification/profile experience; remove excluded menu/dashboard links/counters without deleting shared helpers. Add active-only 15-second polling with hidden-tab pause, backoff and terminal stop; provider calls remain in the worker.
- [ ] Rerun view tests; check UI01/UI02 at narrow/wide widths, keyboard use and failed refresh. Smoke-test the retained teaching-to-feedback flow, including assignment-only gradebook content.
- [ ] Commit UI/status feature with screenshots and exact scenario results; update development guide.

**Acceptance:** UI01/UI02 pass for both student and teacher; P5 verifies actual endpoint authorization. No frontend framework or new navigation system.

## P5: Add teacher rescans, history and protected downloads

**Files:** create `app/scan/ScanAttemptService.php`, `app/controllers/ScanController.php`, `app/controllers/SubmissionFileController.php`, `tests/ScanAccessTest.php`, `tests/ScanStatusTest.php`; extend `Auth.php` only as needed and the task's P0b route/action map. Request template/link changes from P4 instead of editing its files concurrently. Use P1 repository methods rather than adding independent SQL.

**Interfaces:** `ScanAttemptService::requestRescan(int $submissionId, int $actorId, string $requestToken): array` returns `attemptId`, `created`, actual `processingState`; `ScanController::status(int $submissionId): void`; `ScanController::rescan(int $submissionId): void`; `SubmissionFileController::download(int $fileId): void`. Service authorizes and invokes P1's idempotent enqueue; it does not scan. Status JSON matches IP09; rescan returns 202 for created work, 200 for token replay. P3 handles the teacher-origin result consumer.

- [ ] Write failing checks for status JSON fields/unknown-status fallback, no-file not_applicable, owner/cross-course/admin scope, missing file, GET rescan rejection, invalid CSRF, duplicate token, authorized history and direct-path download bypass.
- [ ] Run `php tests/run.php --group=access`; confirm unauthorized cases fail before implementation.
- [ ] Implement authorized status/rescan/history access using the shared repository; publish real request/response examples to P4. Stream authorized private files as attachments; no caller-selected paths or public fallback. P4 renders the shared status/history templates.
- [ ] Run `php tests/run.php --group=status` and `php tests/run.php --group=access`; rerun ST01-ST04 and connected teacher UI paths. Prove a rescan obtains a new analysis ID under the worker; retrieving the old ID is not a fresh scan.
- [ ] Commit feature plus access-control evidence and documentation updates.

**Acceptance:** endpoints enforce object ownership, exact response semantics and approved action scope; both real processing call sites connect. Route map and endpoints have one stream owner; templates have another.

## P6: Run integration, performance, reliability and security experiments

**Files:** extend focused tests; add `tests/load/` runner using existing available tooling, sanitized outputs under `evidence/`; update `validation.md`, `security.md` and `release.md`.

**Interfaces:** consume the built system as documented; do not modify another task's contract silently to make a test pass.

- [ ] Run `php tests/run.php` and scoped role/browser acceptance; record actual failures and fix root causes in the owning task/PR.
- [ ] Run UI02/ST04: retained supporting LMS features work, all assessment links are assignment-only, and excluded direct URLs/actions are denied. Do not claim scope reduction from menu screenshots alone.
- [ ] Execute the full UAT01-UAT34 catalogue and variants from the demo manual, including supporting LMS features. Record actual results, Not run/Pass/Fail/Blocked, defects and retests in sanitized `evidence/uat/` records. The short DM01-DM12 panel script is not a substitute for this manual acceptance run.
- [ ] Execute the load ramp defined in `validation.md` against fake provider responses; report environment, variable, raw metrics, degradation point/tested bound and failure mode.
- [ ] Execute timeout, error, malformed-body and worker-crash trials; measure recovery including operator intervention where required.
- [ ] Perform LIVE01 once with permitted synthetic content, verifying real handle and item/hash relationship. No provider load test or real malware. Record fixture-versus-live distinction.
- [ ] Restore a disposable matched DB/private-file backup and have another member follow setup. Commit sanitized evidence and updated conclusions only.

**Acceptance:** R01-R09 and R15 have linked real evidence or an explicit unresolved failure. No fabricated performance or success claims.

## P7: Reconcile final artifacts and rehearse

**Files:** update canonical docs and final evidence manifest; preserve source artifacts/transcripts only after privacy review and with actual provenance.

- [ ] Reconcile supplied T1/T4, original component/transcript and historical RMMM with the built release; list any still-missing sources honestly.
- [ ] Update all register rows to implemented controls/retries/protocols; record divergences and their reasons.
- [ ] Add actual code/test/evidence links to both case lessons and all trend decisions.
- [ ] Complete each risk's final status, prediction-versus-outcome and reviewer/date; complete actual AI-use log.
- [ ] Have each member demonstrate their feature and explain one failure path; rehearse panel setup from a clean local target. Verify source revision and release contents before submission.
- [ ] Check FR01-FR26/NFR01-NFR12 traceability alongside R01-R15. Preserve unresolved manual defects and unexecuted checks honestly; do not convert a blocked feature into a claimed completed requirement to fit the demo slot.

**Acceptance:** all Parts A-F trace to the system as built. Remaining failures/risks are explicitly reported; final completion is not inferred from this planning document.

Use the SRS and full UAT catalogue as end-goal checks throughout implementation, not a last-day script. DM01-DM12 are the shorter presenter view of the same tested behavior. Any change outside SRS requirements, those cases or a concrete correctness/security need must be proposed before implementation. Read optional prompting guidance in the team guide; members plan their own internal solutions with their agents rather than following a scripted conversation.
