# LMS with VirusTotal Integration

An assignment-focused LMS extending [Doodle](https://github.com/deathlegionteamlk/doodle). Students submit work; teachers receive file-security advisories, can request a fresh scan, and grade/give feedback. Useful course, calendar, announcement and in-app notification features remain. **Quizzes are outside the approved scope.**

Technical 3's original title is *Secure File Submission System with VirusTotal Integration*. Team repository: [ja2gc/lms-virustotal-integration](https://github.com/ja2gc/lms-virustotal-integration).

## Read this before starting

1. [Documentation map](docs/README.md): what every guide is for and what your role must read.
2. [Software Requirements Specification](docs/SRS.md): the authoritative final behavior, role requirements, quality requirements and acceptance criteria. All members and agents must read it.
3. [Feature/workflow overview](docs/feature-scope.md): a readable view of what we keep, exclude and add; it follows the SRS.
4. [Demo and manual UAT guide](docs/demo-manual.md): full-feature manual acceptance checks and the shorter panel sequence using the same cases.
5. [Team guide](docs/team-guide.md): four workstreams, ownership, start/wait dependencies, handoffs and prompting advice.
6. [Implementation plan](docs/implementation-plan.md): assigned task IDs, interfaces, files and checks. Read linked contracts before coding.

Every member should understand this reading order. Every agent must also start with [AGENTS.md](AGENTS.md) and its linked workflow rules. The coordinator assigns actual people to the four workstreams; the repository does not assume a particular member needs a particular amount of code.

## Run the current LMS on localhost

Tested on Windows with **PHP 8.3.30 and SQLite**. You do not need hosting, MySQL, Composer or Node for this baseline. Install PHP with the extensions listed in [the runtime manifest](runtime-requirements.json). If `php` is not recognized, add your PHP folder to PATH; Laragon users can use its terminal or the full path to `php.exe`.

From PowerShell, clone once and start inside the project folder:

```powershell
git clone https://github.com/ja2gc/lms-virustotal-integration.git
cd lms-virustotal-integration
php -v
php -m
New-Item -ItemType Directory -Force tmp/sessions | Out-Null
php -d session.save_path=tmp/sessions -S 127.0.0.1:8173 -t . router.local.php
```

Keep that terminal running and open **[http://127.0.0.1:8173](http://127.0.0.1:8173)**. The first visit opens the installer: choose your own local administrator account/password, install, then sign in. SQLite tables and initial settings are created automatically. No VirusTotal key is needed to try the existing LMS.

Already cloned? Open its folder, pull the latest `main` while your work is committed, then run the last two commands above. Stop the server with **Ctrl+C**; restart with the same command. Your local users/courses remain in the ignored SQLite database. Never delete `.installed` or the database just to restart. Use `127.0.0.1` consistently rather than switching hosts during a session.

In another terminal, run `php tests/run.php`. See [the release/setup guide](docs/release.md) for dependencies, fictional demo accounts, troubleshooting and remaining release gates.

## Current state: working local baseline, integration pending

Installer, all three role logins, course publishing/catalog/enrollment, a written assignment submission, teacher grading and student feedback were checked in the built-in browser preview. Startup/controller/rendering and course/grade route defects were repaired; the sidebar now selects My Courses without also selecting Teacher Studio. See [the baseline audit](docs/baseline-audit.md) for original failures and new evidence.

**VirusTotal, its worker, private attachment downloads and the approved module exclusions are still to implement.** Existing extra menus remain visible. The local router blocks project source/configuration/database files, but upstream uploads remain publicly addressable; use fictional users and synthetic files only. This is a runnable development starting point, not the final integrated release or completed manual UAT. Follow [the implementation plan](docs/implementation-plan.md) and [validation guide](docs/validation.md).

## Shared technical direction

- One PHP application and a CLI background worker from the same codebase, with SQLite and private local files.
- Localhost demonstration; no cloud deployment, new frontend framework or broker is required.
- VirusTotal is the assessed external service. Browser requests go through the LMS; provider keys stay server-side.
- Submission/grade state, processing state and security advisory have different meanings. Use the shared [integration contract](docs/integrations.md) and [data model/ERD](docs/data-model.md).
- Use fictional accounts and synthetic attachments; never commit credentials, local databases, actual student files or backups.
- PHP 8.3.30 is the tested runtime. No runtime upgrade was performed; freeze the tested team environment during integration.

## Repository navigation

| Location | Purpose / status |
| --- | --- |
| `app/core/`, `app/controllers/`, `app/views/`, `app/helpers/` | Existing Doodle authentication/routing/controllers/screens/helpers; inspect before extending |
| `config/`, `database/`, `public/` | Existing configuration/schema/assets; existing public submission storage must be strengthened |
| `docs/` | Authoritative project scope, architecture, contracts, tasks, security, evidence plan and demo guides |
| `evidence/baseline/` | Actual normalized baseline check results; later evidence folders are created when real checks run |
| `AGENTS.md`, `.agents/rules/workflow.md` | Agent reading order, boundaries and verification rules |
| `router.local.php`, `tests/`, `runtime-requirements.json` | Implemented local server entry point, baseline regression checks and tested runtime manifest |
| `app/scan/`, `bin/`, `database/migrations/` | Planned integration areas; the worker and scan migrations do not exist yet |

## Collaboration and completion

One primary owner per file/stream. Merge the baseline sweep before feature branches change shared controllers; merge contracts before consumers connect. Use task branches and reviewed handoffs with exact tests and known limitations. Every member supplies their own verification/evidence. Complete the demo scenarios **and** the final assessment's measured tests/documentation; neither alone is sufficient.

Finish target is November 20, 2026. See the approved scope for the presentation-date assumption and rehearsal sequence. Further scope changes need a proposal before editing. AI assistance is permitted, with the required [AI-use disclosure](docs/ai-use.md); human commits use the actual contributor's identity without AI co-author trailers.

## Upstream provenance

Doodle is by Death Legion Team under the original [license](LICENSE). Its source history and notices are retained. The [upstream README reference](docs/upstream-doodle.md) describes additional upstream features and unverified setup/security claims; it is not this project's feature list or runbook.
