# Local release and demonstration

## Target and current readiness

The coordinator selected localhost. No Render, GitHub Pages runtime or cloud deployment is planned. The existing LMS can now be installed and used locally: follow the tested commands in the [root README](../README.md#run-the-current-lms-on-localhost). Baseline installation, role logins and the written-submission-to-feedback journey were browser-tested on Windows/PHP 8.3.30. This is **not yet the final integrated release**: scanning, private downloads, module exclusions and the full release evidence remain open.

## Tested baseline setup and troubleshooting

The [runtime manifest](../runtime-requirements.json) lists extensions and test requirements. Verify them with `php -v` and `php -m`; enable missing extensions in the active configuration shown by `php --ini`. SQLite is the default: do not set `DOODLE_DB_TYPE=mysql` for this setup. No database server, package install or build step is required. Root, `database/`, `public/uploads/` and `tmp/sessions/` must be writable by your local PHP process.

Start from the repository root, create `tmp/sessions`, and use `php -d session.save_path=tmp/sessions -S 127.0.0.1:8173 -t . router.local.php`. Open `http://127.0.0.1:8173`, complete the installer with your own administrator details, then use the Sign in link. Every clone has its own ignored local DB, install flags, sessions and uploads. None of the fictional verification accounts/passwords is distributed in Git.

For manual baseline practice, admin **Users -> Add user** creates one fictional teacher and student with passwords you choose. Sign in as teacher, **My Courses -> New course -> Publish -> Assignments -> New assignment**. Student uses **Course Catalog -> course -> Enroll now**, then reopens the course overview, opens its assignment and submits a written response. Teacher opens **Teacher Studio -> Grade now**, enters score/feedback and saves. Student sees the result under **My Grades -> Details**. This was the observed baseline journey; it is not a replacement for the full UAT catalogue.

- **PHP not recognized:** use a terminal whose PATH contains PHP, or replace `php` with `& 'C:\path\to\php.exe'` in PowerShell. Do not copy the original upstream quick-start over this command.
- **Could not find driver:** enable `pdo_sqlite` in the PHP configuration actually used by the terminal, then restart PHP.
- **Session permission error or repeated sign-in:** create `tmp/sessions`, start from the repository root with the documented `-d session.save_path` argument, and keep the host/port consistent. The machine's original default session folder was not writable during the check.
- **Port already in use:** stop your earlier server with Ctrl+C or select an unused loopback port and open that exact URL. Do not terminate unrelated processes.
- **Private paths or arbitrary PHP return 404:** expected. Always start with `router.local.php`; a plain `php -S` command ignores `.htaccess` and can expose source files.
- **Stopped/restarted server:** reuse the same DB and `.installed` flag. Do not rerun installation/reset steps on an existing member's data. A disposable clean install belongs in a separate clone/folder.

The baseline router allows only intended entry points and allowlisted public file types. Existing public uploads are still retrievable by URL; P1/P5 must replace this with authorized private downloads. Keep the server on loopback and use synthetic content. Google Fonts/icons are upstream network requests; offline visual fidelity remains a rehearsal check.

## Required release contents

- Source with preserved Doodle history/license and group changes.
- A dependency manifest naming the exact tested PHP version/extensions; external credentials are not dependencies to commit.
- Example configuration with placeholder values, file-size/type limits, private storage location, provider scheduling budgets and explicit fixture/live mode.
- A migration command, web command, worker command, fixture/test command and fictional demo-data setup command.
- Sufficient instructions for a second member/panel to reproduce the system without the implementer's assistance.

## Remaining integration setup contract (planned)

1. Install PHP 8.3 with `pdo_sqlite`, `curl`, `mbstring`, `fileinfo`, `json`, `openssl`, session support and a trusted CA store. Use SQLite for the tested target; other PHP versions/DBs need their own evidence.
2. Check out the release source. Keep credentials in local process configuration, never source. Example variables to implement: `VT_API_KEY`, `SCAN_MODE=fixture|live`, `SCAN_PRIVATE_DIR`, `SCAN_MAX_UPLOAD_BYTES`, `SCAN_REQUESTS_PER_MINUTE`, `SCAN_REQUESTS_PER_DAY`. Reuse upstream `DOODLE_DB_TYPE=sqlite` and `DOODLE_ENV` where compatible.
3. The installer is repaired and creates upstream tables. The separate project migration runner and repeatable fictional demo-data command remain to build. Final acceptance needs two courses with different teachers and synthetic attachments.
4. The implemented web command and router are described above. Extend its access tests when private storage and the explicit action map are added; baseline file blocking alone does not complete P0b/P5.
5. Planned worker command in a second terminal: `php bin/scan-worker.php`. Support a `--once` option for diagnosis/tests. The worker loads CLI configuration, not the web front controller, and must not start sessions or web-trigger migrations.
6. `php tests/run.php` currently runs baseline/routing regressions offline. Add the planned integration groups, then a limited explicitly live synthetic smoke check. Record exact final commands/results once implemented.

Original startup failures and subsequent repaired-baseline results are kept separately in the [baseline audit](baseline-audit.md). A successful baseline does not prove upload hardening, provider availability or complete scope exclusion.

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
