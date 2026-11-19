# Team plan and task record

Read your member row, dependencies and assigned P-task sections. The SRS owns product scope; architecture/data/integration guides own technical contracts. This file owns work division, task checks and completion. Claim Members 1-3 in the group chat; Member 4 is reserved. Use member numbers in repository docs.

## Ownership

| Member | Tasks and file ownership | Current state / dependency |
| --- | --- | --- |
| 1 — upload/data delivery | P1b; immutable private-file storage, validated upload service, Student-controller upload seam, legacy attachment backfill; data/recovery and performance evidence in P6 | Not started. Consume P1a repository; do not add parallel scan SQL. |
| 2 — scanning engine | P2/P3; provider adapter/transport, shared quota pacing, CLI worker, both result consumers and engine fixtures | P2 Done via PR #7; offline adapter verified. P3 not started; consume published P1a and component 1.1.0 through metered transport. |
| 3 — LMS interface | P4; student/teacher templates, scan status/history, polling, CSS/JS, shared navigation/dashboard ownership; UI acceptance evidence | Not started. P0b is Done. Isolated UI fixtures can start; connected status uses P5 and processing uses P3. |
| 4 — shared foundation/HTTP/coordinator | P1a migrations/bootstrap/provider interface/repository, P5 status/rescan/history/private-download HTTP boundaries; P0b cleanup completed; contract review and integration when directed | P0b Done. P1a Done. P5 HTTP implementation verified; connected upload/UI/worker acceptance pending. |

P6/P7 are shared evidence/rehearsal gates. Each member supplies checks, docs and evidence for their code. Members use task branches and PRs; Member 4 reviews contracts/overlaps and integrates reviewed work. Ready for review is not Done. Full process rules are in AGENTS.md and its workflow file.

## Dependencies and parallel work

P0a and P0b are Done. Shared navigation/dashboard templates belong to Member 3. Every member fetches current team main before work and respects the ownership table above.

P1a publishes the session-free bootstrap, migration and repository contract for all consumers. P1b uses that repository to deliver real upload storage and backfill. Members 2/3 can start isolated provider/UI fixtures immediately. Member 2's persistence-backed worker and Member 4's P5 consume verified, published P1a. Real submission-to-scan/download acceptance also requires P1b; processing requires P3. Missing prerequisites remain Waiting for dependency. A fixture pass never closes that integration gate.

```mermaid
flowchart TD
    A[P0a: baseline and common test harness] --> B[P0b: local router and allowed actions]
    A --> C[P1a: shared interface, migrations and repository]
    C --> U[P1b: private storage, uploads and backfill]
    C --> D[P2: tested provider adapter]
    C --> E[P3: worker with fixtures]
    D --> F[P3: connected scanning engine]
    E --> F
    B --> G[P5: authorized HTTP endpoints]
    C --> G
    H[P4: isolated UI and fixtures - start anytime] --> I[P4: connected LMS interface]
    G --> I
    F --> I
    I --> J[P6: integrated checks and measured evidence]
    F --> J
    G --> J
    U --> J
    J --> K[P7: documentation freeze and rehearsal]
```

Work concurrently within these boundaries. Member 1 owns the file producer; Member 2 owns external processing; Member 3 owns presentation; Member 4 owns shared persistence contracts and HTTP access. Tests and evidence belong to the feature owner. No member claims another stream complete.

## Handoff and prompting

Follow [fork synchronization](../.agents/rules/workflow.md#fork-synchronization) before starting/resuming a task, after a dependency merges and before submitting/updating its PR. Send the PR from your fork to the team main, with: branch and revision; changed files; provided interface or JSON example; migration/config requirements; exact test commands and observed results; sanitized evidence; current limitations; which downstream task can now connect. Another member reviews it, then merge before consumers connect. Do not copy chunks of another branch by hand or independently rename fields to make a local version work.

The baseline rendering sweep is complete. Request contract changes from their owner before updating dependent code.

Give your agent a goal, your owned task IDs, relevant SRS requirement IDs, required reading and boundaries. Let it inspect the repository, identify assumptions and propose its own plan. Discuss the plan with it before implementation. You do not need a scripted sequence of follow-up prompts. Add the SRS and applicable UAT cases to your starting prompt; do not ask an agent to infer final requirements from a demo screenshot alone.

Useful follow-ups are evidence-focused: ask what existing code it can reuse, which dependency is missing, how it will test the failure path, what a proposed extra feature contributes to a demo scenario, and which exact checks passed. Use [the demo manual](demo-manual.md) as an end goal, not permission to omit required security/performance evidence.

Keep prompts scoped. Do not tell an agent to build the entire project when it owns one stream. Tell it when a dependency is not ready and ask it to use agreed fixtures without claiming live integration. Use applicable installed skills as required by the workflow rules; repository contracts and the member's decisions remain authoritative.

One optional starting prompt is enough; members discuss solutions with their own agents:

> Read AGENTS.md and linked context. I own Member [number]'s tasks in docs/team-plan.md. Inspect current source/SRS, then plan only my assigned work with me. Respect file ownership and dependency gates; use agreed fixtures for independent preparation. Reuse existing code, write focused failure checks, report exactly what passed, and provide a PR with evidence and updated task records. Do not implement another member's stream or infer scope from screenshots.

## Review focus

1. A crash after provider acceptance but before local handle persistence must not replay scan start (P3).
2. A quota wait beyond the polling deadline must not silently extend it (P3).
3. A text-only update or rejected replacement must not lose the previous valid attachment or show the wrong advisory (P1/P4).
4. A direct static file URL must not bypass the authorized download action (P0/P5).
5. Changed component output or optional provider categories must affect both real consumers consistently (P2/P3).

## Implementation tasks

Only implement assigned tasks. Numerical policies remain in the contracts; do not independently rename IDs, fields or status meanings.

## P0: Make the scoped Doodle baseline reproducible

**P0a files (foundation first):** modify `app/core/Controller.php`, its rendering calls throughout `app/controllers/`, scoped teacher view links, `install.php`, `.gitignore`, `README.md`; create `tests/run.php`, `tests/BaselineTest.php`, `runtime-requirements.json` and `config/scan.example.php`. The runtime manifest/test files now exist; scan example configuration is still pending.

**P0b files (Member 4; Done):** extend existing `app/core/Router.php`, `router.local.php` and routing tests; create `config/routes.php` for the final action map as needed. Own the initial header/navigation/dashboard cleanup before handing those files back to P4. Follow the exact SRS cleanup matrix. The explicit action map denies excluded controllers **and** quiz actions within retained controllers, rather than merely hiding links.

**Interfaces:** preserve public `*/view/{id}` actions; rename the protected rendering helper to `render(string $view, array $data = []): void` and update every internal `$this->view(...)` render call. Preserve `viewRaw`. Align teacher URLs/forms with existing `gradeSubmission(int $id)`. Define CLI-safe loading in P1 rather than requiring `index.php` from jobs.

- [x] Write controller-load checks for all 26 files, plus baseline installation and grade-route checks. The runner follows the discovery contract above and must throw/report nonzero without relying on PHP `assert()` settings.
- [x] Run `php tests/run.php --group=baseline`; observe the reproduced class conflict. Execute the installer in a disposable target to reproduce B07 before fixing bootstrap loading.
- [x] In P0a rename the common renderer/callers and repair grade links/forms and proven installer defects; ignore local sessions/install/migration flags and secrets/data. Verify and merge this baseline sweep before other streams edit controllers/templates.
- [x] Route aliases/class discovery/query links/page blockers and three-role navigation/guest/role checks reviewed in PR #1, merged at 8d15034 and verified on main (16 PHP checks plus JavaScript check). This is not final exclusion.
- [x] Member 4 P0b: implemented the final action map and initial navigation/dashboard cleanup from the [SRS cleanup matrix](SRS.md#cleanup-action-matrix). Extend routing tests to distinguish excluded routes from broken retained routes: block hidden/config/DB/private/docs/evidence paths; deny excluded modules/actions; preserve approved course/material/calendar/announcement/notification/profile/grading paths. Run `php tests/run.php --group=routing` plus direct-URL checks before merging.
- [x] Rerun baseline checks; verify login, enrollment, assignment view, written save, teacher review/grading and student feedback through a browser. Test direct protected paths under the actual router command. Attachment/replacement acceptance remains a separate open gate.
- [x] Add exact local dependency/setup commands to README/release guide, retaining unverified limitations. Commit only this repair task, with evidence; no scanning feature is implied by a green baseline.

**Acceptance:** BT01/BT02, UI02's retained-route smoke check and ST03/ST04 pass; a second member can reproduce installation. No quiz page/action is included in the target interface. Record additional scoped blockers rather than expanding into every upstream feature.

## P1: Shared foundation and upload/data delivery

P1 is complete only when **both P1a and P1b** are reviewed, published and verified. Neither an interface nor fixture evidence certifies real uploads.

### P1a — Member 4: shared persistence contracts

**Files:** `database/migrations/001_scan_foundation.php`, `bin/migrate.php`, `app/scan/bootstrap.php`, `app/scan/ScanMigration.php`, `app/scan/FileScanProvider.php`, `app/scan/ScanAttemptRepository.php`, `tests/ScanPersistenceTest.php`, `tests/Support/ScanFixture.php`. Own repository changes and scan schema; consumers request changes rather than writing parallel SQL.

- [x] Implement and verify repeatable forward migration, revision/attempt identities, pointer ownership and uniqueness.
- [x] Implement and verify revision registration, rescan idempotency, object context/history projections, due claims, guarded result/progress writes and conservative crash/deadline recovery.
- [x] Publish exact constructors, methods, schema details, offline fixtures and test evidence after read-only review. Update this status to Done only after publication and verification.

### P1b — Member 1: private upload/data delivery

**Files:** create `app/scan/ScanSubmissionService.php`, `tests/ScanSubmissionTest.php` and a safe CLI backfill utility; modify `StudentController.php`, web bootstrap, upload configuration and runtime checks as needed. Do not edit Member 3's templates; request necessary form/link changes. Use `ScanAttemptRepository::registerFileRevision` for revision/attempt writes.

**Interface:** `ScanSubmissionService::saveFileRevision(int $submissionId, array $validatedUpload): int` accepts `tmpPath`, `originalName`, `sizeBytes`, `mimeType` and returns immutable file ID. Inject PDO/private root/clock. Use the P1a contract in integrations.md. `FileScanProvider::getResult(string $analysisId, string $expectedSha256): array` is shared unchanged.

- [ ] Write failing tests for real upload error/type/size/MIME/move validation, immutable storage, same-hash separate submissions, replacement, transaction/file failures and text-only updates retaining the current attachment.
- [ ] Implement private immutable storage and the Student-controller service seam. Commit academic content, revision registration and queued work atomically; reject failed uploads without reporting success. No provider calls.
- [ ] Backfill scoped legacy files safely with counts/hash checks and path containment. Coordinate private-download readers with Member 4 and links with Member 3. Preserve original local data; no database reset or public fallback.
- [ ] Run submission/persistence checks and disposable upgrade/backfill twice, including failed replacement and missing legacy file cases. Provide exact fixture-versus-real HTTP evidence.
- [ ] Deliver matched database/private-file recovery and local performance evidence as Member 1's P6 contribution. Have a second member reproduce the procedure. Review/publish/verify before marking P1b Done.

**Acceptance:** P1a proves the reusable repository contract. P1b closes IT01/IT02 persistence/upload and file-failure behavior. P5 closes authenticated download access; P3 closes processing. All remain separate completion gates.

## P2: Implement the reusable VirusTotal result adapter

**Files:** create `app/scan/VirusTotalAdapter.php`, `app/scan/CurlTransport.php`, `tests/ScanAdapterTest.php`, `tests/fixtures/virustotal/`; extend bootstrap explicitly.

**Interfaces:** implement P1a's unchanged `FileScanProvider`; injected HTTP callable and four result fields follow `integrations.md`. Both analysis and item calls use the metered transport. Constructor receives server key and transport; no SQL/session/upload/retry loop.

- [x] Write fixtures and failing checks for each CT01/CT02/CT03 case, nonnegative integer `retryAfter`, input rejection without HTTP, pending analyses, provider errors and file-identity mismatch.
- [x] Run `php tests/run.php --group=adapter`; confirm missing/incorrect behavior fails. Coordinator negative check against the pre-P2 bootstrap failed all 21 cases in a disposable copy; this is review-time evidence, not a reconstructed development history.
- [x] Implement fixed-origin verified TLS requests, ID encoding, strict required-schema checks, conservative normalized classification and sanitized failures. Additive provider fields are tolerated, required identities/counters are enforced.
- [x] Rerun adapter tests without network/key against the production adapter, with no mock classifier bypass. Both real caller checks remain the P3/CT04 acceptance gate.
- [x] Publish tested component-consumption instructions/version and commit this task.

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

**Files:** create `app/views/scan/_status.php`, `app/views/scan/_history.php`, `public/js/scan-status.js`, `tests/ScanViewTest.php`, and UI fixtures under `tests/fixtures/status/`; maintain retained templates and modify student/assignment/teacher templates, shared header/navigation, dashboards, `app/helpers/functions.php` presentation helpers and styles as needed. No HTTP controller is owned by this task.

**Ownership:** Member 4 performs the initial menu/dashboard scope sweep in P0b; P4 receives those files after that merge.

**Interfaces:** consume IP09 status JSON and P5's rescan/download routes exactly as published; use fixture examples while endpoints are built. Own both student and teacher presentation to prevent competing edits to shared partials. Reuse one advisory formatter; never reclassify provider counts in JavaScript.

- [ ] Write failing view tests for pending/retry/completed/Unresolved, text-only not_applicable, replacement freshness, escaped long filenames and preserved form fields. Include approved menu destinations and absence of quiz/excluded links.
- [ ] Run `php tests/run.php --group=ui` and confirm failures.
- [ ] Implement student and teacher views including small scan history, grade/feedback and rescan form consuming P5 endpoints. Retain the approved course/material/calendar/announcement/notification/profile experience; remove excluded menu/dashboard links/counters without deleting shared helpers. Add active-only 15-second polling with hidden-tab pause, backoff and terminal stop; provider calls remain in the worker.
- [ ] Rerun view tests; check UI01/UI02 at narrow/wide widths, keyboard use and failed refresh. Smoke-test the retained teaching-to-feedback flow, including assignment-only gradebook content.
- [ ] Commit UI/status feature with screenshots and exact scenario results; update development guide.

**Acceptance:** UI01/UI02 pass for both student and teacher; P5 verifies actual endpoint authorization. No frontend framework or new navigation system.

## P5: Add teacher rescans, history and protected downloads

**Files:** create `app/scan/ScanAttemptService.php`, `app/controllers/ScanController.php`, `app/controllers/SubmissionFileController.php`, `tests/ScanAccessTest.php`, `tests/ScanStatusTest.php`; extend `Auth.php` only as needed and the task's P0b route/action map. Request template/link changes from P4 instead of editing its files concurrently. Use P1 repository methods rather than adding independent SQL.

**Interfaces:** `ScanAttemptService::requestRescan(int $submissionId, int $actorId, string $requestToken): array` returns `attemptId`, `created`, actual `processingState`; `ScanController::status(int $submissionId): void`; `ScanController::rescan(int $submissionId): void`; `SubmissionFileController::download(int $fileId): void`. Service authorizes and invokes P1a's idempotent enqueue; it does not scan. Status JSON matches IP09; rescan returns 202 for created work, 200 for token replay. P3 handles the teacher-origin result consumer.

- [x] Write failing checks for status JSON fields/unknown-status fallback, no-file not_applicable, owner/cross-course/admin scope, missing file, GET rescan rejection, invalid CSRF, duplicate token, authorized history and direct-path download bypass.
- [x] Run `php tests/run.php --group=access`; confirmed unauthorized cases fail before implementation.
- [x] Implement authorized status/rescan/history access using the shared repository; publish real request/response examples to P4. Stream authorized private files as attachments; no caller-selected paths or public fallback. P4 renders the shared status/history templates.
- [ ] Run `php tests/run.php --group=status` and `php tests/run.php --group=access`; rerun ST01-ST04 and connected teacher UI paths. Prove a rescan obtains a new analysis ID under the worker; retrieving the old ID is not a fresh scan.
- [x] Commit HTTP implementation plus access-control evidence and documentation updates; connected acceptance remains open.

**Current state:** HTTP implementation verified with disposable private-file/repository fixtures. The task remains open for connected P1b uploads, P4 teacher UI and P3 fresh-analysis acceptance. Do not mark P5 wholly Done from HTTP fixture results.

**Acceptance:** endpoints enforce object ownership, exact response semantics and approved action scope; both real processing call sites connect. Route map and endpoints have one stream owner; templates have another.

## P6: Run integration, performance, reliability and security experiments

**Files:** extend focused tests; add `tests/load/` runner using existing available tooling, sanitized outputs under `evidence/`; update `docs/final-project/step-c-performance-reliability-security-validation.md`, `docs/engineering.md` and `docs/final-project/step-a-working-integrated-system.md`.

**Interfaces:** consume the built system as documented; do not modify another task's contract silently to make a test pass.

- [ ] Run `php tests/run.php` and scoped role/browser acceptance; record actual failures and fix root causes in the owning task/PR.
- [ ] Run UI02/ST04: retained supporting LMS features work, all assessment links are assignment-only, and excluded direct URLs/actions are denied. Do not claim scope reduction from menu screenshots alone.
- [ ] Execute the full UAT01-UAT35 catalogue and variants from the demo manual, including supporting LMS features. Record actual results, Not run/Pass/Fail/Blocked, defects and retests in sanitized `evidence/uat/` records. The short DM01-DM13 panel script is not a substitute for this manual acceptance run.
- [ ] Execute the load ramp defined in `docs/final-project/step-c-performance-reliability-security-validation.md` against fake provider responses; report environment, variable, raw metrics, degradation point/tested bound and failure mode.
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
- [ ] Check FR01-FR27/NFR01-NFR12 traceability alongside R01-R15. Preserve unresolved manual defects and unexecuted checks honestly; do not convert a blocked feature into a claimed completed requirement to fit the demo slot.

**Acceptance:** all Parts A-F trace to the system as built. Remaining failures/risks are explicitly reported; final completion is not inferred from this planning document.

Use the SRS and full UAT catalogue as end-goal checks throughout implementation, not a last-day script. DM01-DM13 are the shorter presenter view of the same tested behavior. Any change outside SRS requirements, those cases or a concrete correctness/security need must be proposed before implementation. Use the prompting guidance above; members plan their own internal solutions with their agents rather than following a scripted conversation.

## Completion record

- [x] Documentation foundation, SRS, contracts/diagrams, team guide and full manual acceptance plan published.
- [x] Local installer/renderer/course/grade routes and sidebar selection repaired; localhost runbook and evidence published in `645a43a`.
- [x] Route/role/flashcard/demo-seeder follow-up reviewed, merged via [PR #1](https://github.com/ja2gc/lms-virustotal-integration/pull/1) and verified on main at `8d15034`. Owner: Member 4. Evidence: `evidence/baseline/routes-flashcards.json`.

- [x] Documentation consolidation — Done. Owner: Member 4; [PR #3](https://github.com/ja2gc/lms-virustotal-integration/pull/3), merged at `31e9424` and reverified on main. Reduced docs from 21 to 13, removed obsolete scope/index/handoff files, used member numbers and separated original Parts A-F. Read-only review corrections applied; 73 local links, all FR/NFR/R/P/RK/UAT IDs and unchanged technical contract blocks verified; `git diff --check` passed. Application files are unchanged. Runtime implementation status is recorded by the P-task checks and current completion records below.

For every task/PR append: member number, task ID, branch/PR/merge, actual behavior/files, exact checks/results, sanitized evidence, contract/config/migration effects, downstream readiness and unresolved dependencies. Use Ready for review before integration; Done only after reviewed/merged verification. Update P-task checkboxes. Keep personal-name assignments in team communication.

- [x] Fork collaboration rules — Done. Owner: Member 4; [PR #5](https://github.com/ja2gc/lms-virustotal-integration/pull/5), reviewed, merged at `b4d80b9` and reverified on main. Added synchronization triggers, remote verification, preservation/divergence/conflict rules and fork-to-team PR flow; linked the agent entry, README and handoff guidance. Documentation-only evidence; runtime completion is recorded separately below. Verified 77 local links, preserved requirement/case IDs and technical contract blocks, no runtime-file changes and clean `git diff --check`.

The baseline route/flashcard/seeder follow-up is Done via PR #1/source merge 8d15034. It passed 16 PHP checks, the JavaScript check, 131 syntax checks and 145 links at that snapshot. Raw evidence remains under evidence/baseline/. P0b cleanup is recorded separately below. Private uploads and VirusTotal remain pending as the P-task checks show.


- [x] P0b — Done; published and reverified on main at `669569b`. Owner: Member 4; branch `codex/member4-p0b`, based on fetched team main `bd684d7`. The coordinator explicitly authorized publication to main in this chat, PR reference: not applicable. Applied the SRS action map before controller loading; removed excluded menus/promotions/dashboard counters, OAuth/Push setting writes and quiz grade displays/queries. Preserved Backup, manual flashcards, account actions, schema history/local data and still-called Gamification internals. Prevented stored excluded notification links from reopening disabled actions. Retired offline worker/cache behavior and added private/no-store headers. No migration or scan contract changed. Checks: 22 PHP checks, 2 Node checks, 133 PHP syntax checks, and native in-app three-role dashboard/login/logout inspection. Evidence: [P0b](../evidence/cleanup/p0b.json). Independent read-only review found no remaining material issue after corrections. Full UAT, historical-worker browser upgrade and wide layout acceptance are not claimed.
  Shared-view ownership has transferred back to Member 3 after publication/verification of this reviewed change: `app/views/layout/header.php`, role dashboards and retained templates. Member 4 keeps the route map/HTTP boundary. P5 consumes published P1a; its current implementation/acceptance record is separate below. P1b private upload integration and P3 processing remain required for end-to-end acceptance. Members 2/3 can work on agreed isolated fixtures now. Do not mark P1b, P5 or P6/P7 Done from P0b evidence.

- [x] P1a — Done; published at `f881393`, fetched revision verified and all 15 persistence checks rerun successfully. Owner: Member 4; branch `codex/shared-p1-foundation`, base `eb5e124`. Implemented SQLite versioned migration/CLI-safe bootstrap, provider interface and shared repository, including Member 1 revision-registration seam. Evidence: [P1a](../evidence/foundation/p1a.json). Checks: 37 PHP checks including 15 persistence checks, two Node checks and 141 syntax checks; read-only review corrections verified. No local database migrated. P1b/P2/P3/P4 remain unfinished; P5 has its own implementation/acceptance record below; fixture rows do not prove uploads or scanning. Coordinator-authorized main publication; no PR.

- [ ] P5 — HTTP implementation published at `d13877f` and reverified on main; connected acceptance open. Owner: Member 4; branch `codex/member4-p5`, base `f881393`. Added authorized JSON status/history/rescan and attachment streaming through P1a; no controller SQL or provider calls. Added server-issued rescan tokens, CSRF-array rejection, no-store headers and direct legacy/private path denial, including repeated-slash/Windows short-name aliases. Evidence: [P5 HTTP](../evidence/access/p5.json). Checks: 42 PHP checks (access 3, status 2, persistence 15 plus retained checks), two Node checks, 147 syntax checks and native in-app fixture status/history inspection. Independent read-only review correction verified. No local database migrated. Member 1 P1b must produce/import private bytes; Member 3 P4 must add file-ID links/scan UI; Member 2 P3 must prove a fresh analysis. P5 remains open for those connected acceptance checks. No PR; coordinator-authorized main publication.

- [x] Local environment setup — Done; published at `5cde3fa` and reverified on main (configuration 6/6, baseline 7/7; only `.env.example` tracked, local `.env` ignored). Owner: Member 4; branch `codex/local-env-setup`, base `6e17e0a`. Added a blank `.env.example`, allowlisted local configuration loading for web/CLI, broad real-env Git ignores and release exclusions. README explains private key retrieval/entry, fixture/live settings and Git checks. Tests isolate developer keys/live mode/database/private-root settings, including Windows variable casing. The installer fixture includes the new loader. Checks: 48 PHP checks (configuration 6), two Node checks and 149 syntax checks; read-only review findings reproduced and corrected. Local `.env` is ignored and untracked; no real API key was obtained, displayed or committed. No live request or release build was executed. This setup does not complete Member 2 P2/P3 or any connected acceptance gate. Evidence: [local configuration](../evidence/configuration/local-env.json).

- [x] P2 — Done within offline component scope; owner: Member 2; branch `codex/member2-p2`, based on fetched team main `e3ecbbc`; [PR #7](https://github.com/ja2gc/lms-virustotal-integration/pull/7), reviewed at `8d6d453` and merged at `a6285be`. Added the VirusTotal result adapter, cURL transport, explicit bootstrap loading and offline provider fixtures. Member 4 accepted optional `quotaKind` as component 1.1.0; required provider fields/signature/classification remain unchanged. Coordinator and independent read-only review found no blocking runtime issue; no coordinator runtime fix or removal was needed. Checks: `php tests/run.php --group=adapter` (21 passed), `php tests/run.php` on merged main (69 passed), both Node checks, 152 PHP syntax checks and `git diff --check`. Native in-app localhost checks used a disposable fictional DB for student/teacher/admin dashboards, logout and student submission-form rendering. Evidence: [P2](../evidence/contracts/p2.json), including negative-test provenance and limits. No user's DB migration, live provider request, actual cURL transfer, worker or end-to-end scan is claimed. P3 requires metered transport and both real result consumers; P1b/P4/P5/P6/P7 acceptance remains open. Original Technical 3 PHP/transcript remain unavailable for comparison. Member 4 publishes the canonical contract/status/evidence follow-up directly to main under coordinator authorization.
