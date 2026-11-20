# LMSTotal

By group MuscleMan.

An assignment-focused LMS extending [Doodle](https://github.com/deathlegionteamlk/doodle). Students submit written work and private attachments. A background worker requests VirusTotal analysis; teachers review advisories, request fresh scans and provide grades/feedback.

Advisories are **Flagged**, **Review Recommended**, **No Detections Reported**, or **Unresolved**. They do not guarantee a file is safe. Use fictional accounts and synthetic files for this local demonstration.

## Run the current LMS on localhost

Requirements: Git, PHP 8.3 with extensions in [runtime-requirements.json](runtime-requirements.json), and SQLite. Node.js is optional for JavaScript checks and browser recording.

```powershell
git clone https://github.com/ja2gc/lms-virustotal-integration.git
cd lms-virustotal-integration
New-Item -ItemType Directory -Force tmp/sessions, tmp/uploads | Out-Null
php -d session.save_path=tmp/sessions -d upload_tmp_dir=tmp/uploads -d upload_max_filesize=10M -d post_max_size=12M -S 127.0.0.1:8173 -t . router.local.php
```

Open [localhost](http://127.0.0.1:8173), complete the installer once, then use another terminal in the project folder:

```powershell
php bin/migrate.php
php bin/seed-demo.php
php bin/scan-worker.php
```

Keep the server and worker running. Restart with the same commands; do not delete the database or reinstall. Existing clones can use `git pull --ff-only` after preserving local changes. Never replace a folder containing local work.

### Fictional localhost demo accounts

| Role | Username | Local demo password |
| --- | --- | --- |
| Administrator | `panel_admin` | `DemoOnly!2026` |
| Teacher | `panel_teacher` | `DemoOnly!2026` |
| Student | `panel_student` | `DemoOnly!2026` |
| Other course teacher/student | `panel_teacher_b` / `panel_student_b` | `DemoOnly!2026` |

These public credentials are for fictional localhost data only. Seeding is repeatable and does not reset changed accounts.

## VirusTotal API key on your own computer

Each member obtains their own key from [VirusTotal's API key page](https://www.virustotal.com/gui/my-apikey). Create local configuration once:

```powershell
if (-not (Test-Path -LiteralPath .env)) { Copy-Item .env.example .env }
notepad .env
```

Enter the key privately as `VT_API_KEY=...`. Keep `SCAN_MODE=fixture` for offline demonstrations; use `SCAN_MODE=live` for permitted online tests. Restart PHP and the worker after changes. Never share/commit the key or show `.env` in a recording. Only the empty `.env.example` is tracked; `.env` is ignored. Existing process settings take priority.

Uploads allow PDF, TXT and DOCX up to 10 MiB. Private bytes default to `private/submissions`; `SCAN_PRIVATE_DIR` can point outside the project. Inspect legacy attachments with `php bin/import-legacy-submissions.php`; add `--apply` only after a matched database/files backup. Originals are preserved and old public URLs are blocked.

The worker supports `--once`, offline `--fixture-case=malicious` (also suspicious, inconclusive, pending, timeout, auth, quota, malformed, no-detections), and `--resume-provider` after correcting a provider pause. Offline results are explicitly labeled. Live analysis is asynchronous and subject to quotas. Browser refreshes never start provider requests.

## Checks

```powershell
php tests/run.php
node tests/flashcard-study.test.cjs
node tests/service-worker.test.cjs
node tests/scan-status.test.cjs
```

Tests use disposable databases/private files. [Validation](docs/final-project/step-c-performance-reliability-security-validation.md) records measured results and verification scope. Small sanitized `evidence/` records support validation; generated diagnostics, recordings, databases and private files belong in ignored local folders.

## Project documents

| Guide | Purpose |
| --- | --- |
| [Requirements](docs/SRS.md) | Scope, behavior and acceptance criteria |
| [Architecture](docs/architecture.md), [data model](docs/data-model.md), [integration contracts](docs/integrations.md) | Components, persistence and provider/HTTP semantics |
| [Engineering](docs/engineering.md), [team record](docs/team-plan.md), [demo/UAT guide](docs/demo-manual.md) | Development, delivered contributions, verification and demonstration |
| [A — System delivery](docs/final-project/step-a-working-integrated-system.md) | Local setup, configuration and recovery |
| [B — Integration design](docs/final-project/step-b-integration-documentation.md) | Boundaries and reusable component |
| [C — Validation](docs/final-project/step-c-performance-reliability-security-validation.md) | Performance, reliability and security |
| [D — Engineering decisions](docs/final-project/step-d-trends-evaluation.md) | Architecture trade-offs |
| [E — Case lessons](docs/final-project/step-e-case-based-lessons.md) | Traceable lessons applied to the system |
| [F — Risks and review](docs/final-project/step-f-risk-closure-and-ai-use-log.md) | Risk outcomes and AI-use disclosure |

## Troubleshooting

- PHP missing: add its folder to PATH. SQLite errors: enable `pdo_sqlite`. Live transport needs cURL and a trusted CA store.
- Session/upload temporary-file errors: create `tmp/sessions` and `tmp/uploads` and use the documented command. Use `127.0.0.1` consistently.
- Upload errors: use the documented PHP limits and writable private storage; run the scan migration explicitly.
- Port busy: stop your earlier server or select another loopback port. Always use `router.local.php`; PHP's built-in server does not enforce `.htaccess`.

Doodle is by Death Legion Team under the original [GPL license](LICENSE). Original notices/history remain. This project targets local demonstration rather than production deployment.
