# Team plan and task record

The SRS owns behavior; architecture/data/integration guides own technical contracts. This file records current responsibility, delivery status and remaining acceptance work.

## Ownership

| Member | Current responsibility |
| --- | --- |
| 1 | Reproduce setup, demonstrate the integrated workflow and record the assigned segment |
| 2 | P2 adapter contribution; explain provider integration and reproduce the demo |
| 3 | Reproduce setup, demonstrate the interface and record the assigned segment |
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
- [ ] Every full UAT01-UAT35 variant, including independent human acceptance, signed off.
- [ ] A second member/machine reproduces setup and completes the final presentation recording.

## P7: Documentation and release

- [x] Concise README; A-F project guides reconciled with source and measured outcomes.
- [x] Upstream attribution, actual review/disclosure and sanitized validation retained.
- [x] Temporary diagnostics/recordings/private data kept outside tracked deliverables.
- [ ] Original T1/T4/register/component/transcript provenance supplied and reconciled where still unavailable.
- [ ] Team rehearsal and stakeholder acceptance recorded.

## Completion record

Baseline before this integrated completion: `f2dec3e`. Member 4 completed storage/import, metered worker/two consumers, views, connected checks and fresh-install/rehearsal verification. Member 2 retains attribution for the merged P2 component. Publication uses one combined completion commit with the configured human Git identity and no AI author/coauthor trailers.

Read-only reviews checked persistence/upload/UI and worker boundaries. Regression corrections addressed DOCX inspection, import-count rollback, invalid new-upload rendering, contradictory advisory display, fixture provenance, SQLite enrollment/progress, announcement audience the real provider's completed-handle alias with atomic freshness protection, and writable upload temporary directories.

Current evidence: [integrated summary](../evidence/validation/summary.json), [load](../evidence/performance/summary.json), [reliability](../evidence/reliability/summary.json), [live exchange](../evidence/live/synthetic-check.json), [manual acceptance status](../evidence/uat/acceptance-status.json). Detailed test counts belong to that snapshot. Private bytes are retained for reviewed operator cleanup; no existing user database, uploads or environment contents were inspected/reset.

The completed real provider result is **Unresolved / inconclusive_engines** because unsupported engines prevent a favorable conclusion under the agreed policy. This is a valid completed advisory. The later result read is not proof of a continuous live browser/worker run within its deadline; scheduled timing/recovery is tested separately with fixtures. Missing human/source gates above remain open.
