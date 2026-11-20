# Team delivery record

The SRS owns behavior; architecture/data/integration guides own technical contracts. This file records delivered contributions, verification status and acceptance limits.

## Ownership

| Member | Delivered contribution |
| --- | --- |
| 1 | Final project document |
| 2 | P2 result adapter contribution, reviewed and merged through PR #7; presentation slides and scripts |
| 3 | [System demo video](demo/lmstotal-final-demo.mp4), separate from the final presentation slides and scripts |
| 4 | Integrated implementation, source review, verification, repository and documentation maintenance |

Members use the same team release and their own ignored API keys. Reproduction needs a clone, not a fork or feature branch. New code changes follow the shared synchronization/review workflow.

## Dependencies and acceptance

Private file revisions create durable work. The worker starts a new analysis, stores its handle and obtains validated evidence. Both automatic and teacher-requested consumers use one adapter. Views consume authorized status/history/download/rescan routes. Provider failure never rolls back accepted academic work.

Component checks, connected fixtures, real provider exchange, full manual acceptance and independent setup are separate evidence categories. A pass in one category does not imply the others.

## P0: Application baseline

- [x] Local installation, role navigation, rendering and assignment grading routes.
- [x] Explicit public action map and private/source/static-path boundaries.
- [x] Assignment-focused menus, manual flashcards and retained supporting workflows.

## P1: File delivery and persistence

### P1a: Shared foundation

- [x] Explicit repeatable SQLite migration, class-only bootstrap and shared repository.
- [x] Immutable revision identities, idempotent requests, leases and guarded result writes.

### P1b: Private uploads

- [x] Genuine HTTP upload, size/extension/MIME/DOCX validation and private immutable byte storage.
- [x] Atomic academic save/revision/queue with rollback of only new bytes.
- [x] Rejected replacement and text-only updates retain the current file.
- [x] Contained dry-run/apply legacy import, repeatability and matched disposable restore checks.

## P2: Provider result adapter

- [x] [PR #7](https://github.com/ja2gc/lms-virustotal-integration/pull/7), Member 2 contribution, reviewed and merged at `a6285be`.
- [x] Conservative classification, two-read result contract, fixed-origin transport and sanitized failures.
- [x] Component patch 1.1.1 validates the observed upload-descriptor/canonical-ID alias using timestamp, SHA-256 and relationship MD5; unexpected IDs remain Unresolved.

## P3: Durable worker

- [x] Shared persisted budget, lookup/start, stored handles and two real result consumers.
- [x] Unknown POST outcomes do not replay; leases, polling deadlines and bounded retries are enforced.
- [x] Fixture CLI, provider pause/resume and restart/deadline/quota/failure checks.

## P4: Interface

- [x] Status/history/counts, private file-ID links and advisory/grade separation.
- [x] Active polling, hidden-tab pause, error backoff, terminal stop and idempotent rescan replay.
- [x] Escaping, invalid-input retention, fixture provenance and responsive browser inspection.

## P5: Authorized HTTP boundaries

- [x] Owner/course-teacher/admin authorization, POST/CSRF/token semantics and static-path denial.
- [x] Actual multipart upload/download plus automatic and fresh teacher processing through connected fixtures.

## P6: Verification

- [x] Full PHP/JavaScript/syntax checks and fresh actual installer -> migration -> repeatable seeder -> CLI worker -> private download.
- [x] Supported administrative, teaching, learning, calendar, profile, announcement and grading workflows exercised locally.
- [x] Full 1/5/10/20-client load ramp, deterministic fault trials and matched disposable restore.
- [x] Real synthetic upload/start, exact analysis/file relationship and completed provider evidence retrieved.
- [x] Playwright video rehearsal with animated cursor/click highlights, student upload, advisory, teacher rescan/history and feedback.

## P7: Documentation and release

- [x] Concise README; A-F project guides reconciled with source and measured outcomes.
- [x] Upstream attribution, actual review/disclosure and sanitized validation retained.
- [x] Temporary diagnostics/recordings/private data kept outside tracked deliverables.
- [x] Final project document completed by Member 1.
- [x] Final presentation slides and scripts completed by Member 2.
- [x] System demo video completed by Member 3, separate from the final presentation.

## Completion record

Baseline before this integrated completion: `f2dec3e`. Member 4 completed storage/import, metered worker/two consumers, views, connected checks and fresh-install/rehearsal verification. Member 2 retains attribution for the merged P2 component. The team confirmed delivery attribution on 2026-11-21: Member 1 prepared the final document, Member 2 prepared the final presentation slides and scripts, and Member 3 made the separate system demo video. These delivery records reflect team confirmation; linked verification records cover the application checks and local rehearsal.

Read-only reviews checked persistence/upload/UI and worker boundaries. Regression corrections addressed DOCX inspection, import-count rollback, invalid new-upload rendering, contradictory advisory display, fixture provenance, SQLite enrollment/progress, announcement audiences, the real provider's completed-handle alias with atomic freshness protection, and writable upload temporary directories.

Current evidence: [integrated summary](../evidence/validation/summary.json), [load](../evidence/performance/summary.json), [reliability](../evidence/reliability/summary.json), [live exchange](../evidence/live/synthetic-check.json), [manual acceptance status](../evidence/uat/acceptance-status.json). Detailed test counts belong to that snapshot. Private bytes are retained for reviewed operator cleanup; no existing user database, uploads or environment contents were inspected/reset.

The completed real provider result is **Unresolved / inconclusive_engines** because unsupported engines prevent a favorable conclusion under the agreed policy. This is a valid completed advisory. The later result read is not proof of a continuous live browser/worker run within its deadline; scheduled timing/recovery is tested separately with fixtures. See the validation guide for the scope of recorded checks.
