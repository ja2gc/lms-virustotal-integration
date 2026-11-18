# Step A — Working Integrated System

## Original submission instruction — Part A

> Deliver a running system in which at least TWO (2) systems or services exchange data or capability through middleware, APIs, or enterprise service technologies. Submit source code, a dependency manifest, configuration, and build and run instructions sufficient for the panel to run it without your help.

## Project response

The working local Doodle baseline is verified; the final LMS-to-VirusTotal exchange and worker remain pending. [README setup](../../README.md#run-the-current-lms-on-localhost) is authoritative for actual app/seeder commands and demo credentials. [Runtime manifest](../../runtime-requirements.json) lists tested PHP/extensions. The team repository preserves Doodle history/license.

## Release contents

- Source with preserved Doodle history/license and group changes.
- A dependency manifest naming the exact tested PHP version/extensions; external credentials are not dependencies to commit.
- Example configuration with placeholder values, file-size/type limits, private storage location, provider scheduling budgets and explicit fixture/live mode.
- A migration command, web command, worker command, fixture/test command and fictional demo-data setup command.
- Sufficient instructions for a second member/panel to reproduce the system without the implementer's assistance.

## Remaining release work

- [ ] Provide safe example configuration: `VT_API_KEY`, `SCAN_MODE=fixture|live`, `SCAN_PRIVATE_DIR`, `SCAN_MAX_UPLOAD_BYTES`, `SCAN_REQUESTS_PER_MINUTE`, `SCAN_REQUESTS_PER_DAY`. Reuse `DOODLE_DB_TYPE=sqlite`/`DOODLE_ENV`; use process configuration, never committed real credentials. Values/units follow the integration and security contracts.
- [x] P1a provides explicit `php bin/migrate.php` and session-free `app/scan/bootstrap.php`, verified twice on a disposable installed baseline. The installer/seeder do not apply project scan migrations.
- [ ] Implement `php bin/scan-worker.php` with `--once`, explicit fixture/live mode and no browser-session/web-migration dependency.
- [ ] Complete P1b private upload storage/backfill and P4 file-ID links. P5 authorized download HTTP behavior is fixture-verified; integrated attachment delivery remains open. P0b action scope is complete.
- [ ] Execute full offline/integration checks, limited live synthetic exchange and second-machine reproduction; record final commands/revision/evidence.

## Release gate and rollback

Run focused tests, then the complete task-scoped harness and role/browser workflow. A second member follows setup from a clean disposable installation. Record revision, commands and outcomes. Use a named release tag only after these checks pass; keep documentation synchronized with the tagged source.

Before a schema release, stop web writes and worker, copy the SQLite DB plus private file tree to a protected local backup, and verify restore in a different temporary target. Do not commit or share the backup. Resume only when migration exits successfully and scoped workflows pass. If migration fails, keep the worker stopped, preserve evidence and restore the matched application/DB/files backup to the disposable demo environment. Never run destructive reset commands against an unverified local path.

The final submission must include measured recovery/restore proof, not just this plan. Technical 4 was not supplied; reconcile it here before release and record any divergence in [architecture](../architecture.md).

## Assessment traceability

| ID | Observable acceptance criterion | Final part | Design and required proof |
| --- | --- | --- | --- |
| R01 | Panel starts the app and worker from a clean local setup using documented dependencies and configuration | A | `docs/final-project/step-a-working-integrated-system.md`; clean-machine setup record; dependency manifest and example configuration |
| R02 | LMS exchanges real data with VirusTotal and binds the returned analysis to the submitted file revision | A/B | `integrations.md`; limited live synthetic-file trace; fixture mode separately labeled |
| R15 | Approved LMS support features remain usable; assignments are the only assessed activity; excluded menus/actions cannot be entered by direct URLs | A/B | `docs/SRS.md`; UI02 and ST04; documented action allowlist |
