# Local release and demonstration

## Target and current readiness

The coordinator selected localhost. No Render, GitHub Pages runtime or cloud deployment is planned. This is the target release procedure; it is **not yet a panel-ready runbook**. The first implementation task must fix the [baseline blockers](baseline-audit.md), add the safe local router/bootstrap and validate installation. Replace the proposed commands below with proven commands and attach their output before final submission.

## Required release contents

- Source with preserved Doodle history/license and group changes.
- A dependency manifest naming the exact tested PHP version/extensions; external credentials are not dependencies to commit.
- Example configuration with placeholder values, file-size/type limits, private storage location, provider scheduling budgets and explicit fixture/live mode.
- A migration command, web command, worker command, fixture/test command and fictional demo-data setup command.
- Sufficient instructions for a second member/panel to reproduce the system without the implementer's assistance.

## Proposed local setup contract

1. Install PHP 8.3 with `pdo_sqlite`, `curl`, `mbstring`, `fileinfo`, `json`, `openssl`, session support and a trusted CA store. Use SQLite for the tested target; other PHP versions/DBs need their own evidence.
2. Check out the release source. Keep credentials in local process configuration, never source. Example variables to implement: `VT_API_KEY`, `SCAN_MODE=fixture|live`, `SCAN_PRIVATE_DIR`, `SCAN_MAX_UPLOAD_BYTES`, `SCAN_REQUESTS_PER_MINUTE`, `SCAN_REQUESTS_PER_DAY`. Reuse upstream `DOODLE_DB_TYPE=sqlite` and `DOODLE_ENV` where compatible.
3. Run the repaired installer against a new disposable local DB, then the project migration runner. Create fictional student/teacher/admin users, two courses with different teachers, one assignment per course, and synthetic attachments. Normal demo-data setup must run offline and be repeatable.
4. Planned web command: `php -S 127.0.0.1:8080 router.local.php`. `router.local.php` must be implemented and access-tested; do not use upstream `.htaccess` as protection for the built-in server.
5. Planned worker command in a second terminal: `php bin/scan-worker.php`. Support a `--once` option for diagnosis/tests. The worker loads CLI configuration, not the web front controller, and must not start sessions or web-trigger migrations.
6. Planned checks: `php tests/run.php` offline, then a small optional explicitly live smoke check using only synthetic content. Record exact final commands once implemented.

Do not copy a generic README quick-start and label it verified: source inspection found an installer-bootstrap concern, controller-load errors and a grade route mismatch.

## Demo sequence

Use [the demo/manual UAT guide](demo-manual.md) for the full manual acceptance catalogue, defect/retest recording and the shorter presenter sequence. The steps below summarize its release connection; setup/run commands remain authoritative in this release guide. Complete and record UAT01-UAT34/variants before calling the release accepted; a rehearsed happy path alone is insufficient.

1. Teacher opens a course/material and publishes an assignment/deadline. Briefly establish retained dashboards, calendar, announcements and notifications; avoid a tour of unrelated pages.
2. Student submits text/file, sees successful local save and pending advisory while the worker is stopped.
3. Start worker in fixture mode and show the result bound to the correct revision. Clearly label simulated provider behavior.
4. Teacher sees counts/history, requests a fresh rescan, grades and gives feedback. Student sees grade/feedback and the in-app notification. No quizzes are in scope.
5. Replace a file mid-scan and show an old result cannot become the replacement's advisory.
6. Show one injected failure and retained submission; use prepared measurements for the other timeout/error/malformed-response/recovery cases rather than waiting through all retries on stage.
7. Show denied cross-user/course access and excluded direct routes.
8. Show sanitized real VirusTotal evidence. Run one limited live synthetic check if internet/key permit; otherwise distinguish prior real evidence from fixtures. A fixture-only run does not prove live integration.

Follow the November 20 finish target and working presentation-date assumption in [feature scope](feature-scope.md). Reserve the final working session for evidence, second-machine setup and rehearsal, not optional features or a PHP migration. Confirm the panel's allotted time. Every member explains their owned module using the same fixture accounts/files/status meanings.

## Release gate and rollback

Run focused tests, then the complete task-scoped harness and role/browser workflow. A second member follows setup from a clean disposable installation. Record revision, commands and outcomes. Use a named release tag only after these checks pass; keep documentation synchronized with the tagged source.

Before a schema release, stop web writes and worker, copy the SQLite DB plus private file tree to a protected local backup, and verify restore in a different temporary target. Do not commit or share the backup. Resume only when migration exits successfully and scoped workflows pass. If migration fails, keep the worker stopped, preserve evidence and restore the matched application/DB/files backup to the disposable demo environment. Never run destructive reset commands against an unverified local path.

The final submission must include measured recovery/restore proof, not just this plan. Technical 4 was not supplied; reconcile it here before release and record any divergence in `architecture.md`.
