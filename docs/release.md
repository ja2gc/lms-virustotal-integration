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

1. Show student submission, successful local save and pending advisory while the worker is stopped.
2. Start worker in deterministic fixture mode; show result tied to the right revision. Clearly label simulated provider behavior.
3. Show teacher review and a new teacher-requested analysis, with history retained.
4. Replace a file while its previous scan is pending; show the old result cannot become the replacement's advisory.
5. Inject timeout, error and malformed response; show saved work, unresolved/waiting state and measured recovery behavior.
6. Demonstrate denied cross-user/course reads/downloads/rescans.
7. Show sanitized evidence from a real VirusTotal request. If internet/key is available, run one limited live synthetic demonstration; otherwise label prior evidence and fixture behavior honestly. A fixture-only run does not prove live integration.

## Release gate and rollback

Run focused tests, then the complete task-scoped harness and role/browser workflow. A second member follows setup from a clean disposable installation. Record revision, commands and outcomes. Use a named release tag only after these checks pass; keep documentation synchronized with the tagged source.

Before a schema release, stop web writes and worker, copy the SQLite DB plus private file tree to a protected local backup, and verify restore in a different temporary target. Do not commit or share the backup. Resume only when migration exits successfully and scoped workflows pass. If migration fails, keep the worker stopped, preserve evidence and restore the matched application/DB/files backup to the disposable demo environment. Never run destructive reset commands against an unverified local path.

The final submission must include measured recovery/restore proof, not just this plan. Technical 4 was not supplied; reconcile it here before release and record any divergence in `architecture.md`.
