# Step A — Working Integrated System

## Overview

The local LMS includes private upload revisions, asynchronous processing, shared quota reservations, advisory/history views and protected downloads. Live-provider verification is recorded separately from fixtures in validation evidence. [README setup](../../README.md#run-the-current-lms-on-localhost) is authoritative for actual app/seeder commands and demo credentials. [Runtime manifest](../../runtime-requirements.json) lists tested PHP/extensions. The team repository preserves Doodle history/license.

## Release contents

- Source with preserved Doodle history/license and group changes.
- A dependency manifest naming the exact tested PHP version/extensions; external credentials are not dependencies to commit.
- Example configuration with placeholder values, file-size/type limits, private storage location, provider scheduling budgets and explicit fixture/live mode.
- A migration command, web command, worker command, fixture/test command and fictional demo-data setup command.
- Sufficient instructions for a second member/panel to reproduce the system without the implementer's assistance.

## Implemented release capabilities

- [x] Provide safe example configuration: `VT_API_KEY`, `SCAN_MODE=fixture|live`, `SCAN_PRIVATE_DIR`, `SCAN_MAX_UPLOAD_BYTES`, `SCAN_REQUESTS_PER_MINUTE`, `SCAN_REQUESTS_PER_DAY`. Reuse `DOODLE_DB_TYPE=sqlite`/`DOODLE_ENV`; use process configuration, never committed real credentials. Values/units follow the integration and security contracts.
- [x] P1a provides explicit `php bin/migrate.php` and session-free `app/scan/bootstrap.php`, verified twice on a disposable installed baseline. The installer/seeder do not apply project scan migrations.
- [x] Implement `php bin/scan-worker.php` with `--once`, explicit fixture/live mode and no browser-session/web-migration dependency.
- [x] Implement private upload/backfill, file-ID links and connected protected attachment delivery. Automatic processing and teacher-requested fresh analysis pass connected fixture checks. Original local data is preserved; imports require explicit application.
- [x] Execute local offline/integration checks, actual fresh installation, limited real synthetic exchange and recording rehearsal; record results in validation.

## Release gate and rollback

Run focused tests, then the complete task-scoped harness and role/browser workflow. A second member follows setup from a clean disposable installation. Record revision, commands and outcomes. Use a named release tag only after these checks pass; keep documentation synchronized with the tagged source.

Before a schema release, stop web writes and worker, copy the SQLite DB plus private file tree to a protected local backup, and verify restore in a different temporary target. Do not commit or share the backup. Resume only when migration exits successfully and scoped workflows pass. If migration fails, keep the worker stopped, preserve evidence and restore the matched application/DB/files backup to the disposable demo environment. Never run destructive reset commands against an unverified local path.

A matched database/private-file restore passes in a disposable target; see submission tests and validation.

## Verification mapping

| ID | Observable acceptance criterion | Final part | Design and required proof |
| --- | --- | --- | --- |
| R01 | Panel starts the app and worker from a clean local setup using documented dependencies and configuration | A | `docs/final-project/step-a-working-integrated-system.md`; clean-machine setup record; dependency manifest and example configuration |
| R02 | LMS exchanges real data with VirusTotal and binds the returned analysis to the submitted file revision | A/B | `integrations.md`; limited live synthetic-file trace; fixture mode separately labeled |
| R15 | Approved LMS support features remain usable; assignments are the only assessed activity; unlisted actions cannot be entered by direct URLs | A/B | `docs/SRS.md`; UI02 and ST04; documented action allowlist |
