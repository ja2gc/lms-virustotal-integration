# LMS with VirusTotal Integration

An assignment-focused LMS extending [Doodle](https://github.com/deathlegionteamlk/doodle). Technical 3's original title is *Secure File Submission System with VirusTotal Integration*. Team repository: [ja2gc/lms-virustotal-integration](https://github.com/ja2gc/lms-virustotal-integration).

## Start here

Every member reads the SRS, their current ownership row and task sections in the team plan, and the demo/UAT targets. Member 1 owns P1b upload/data delivery; Member 2 P2/P3 scanning; Member 3 P4 UI; Member 4 P1a shared persistence and P5 HTTP access. P1a and P1b have separate completion gates. Agents start with AGENTS.md and its workflow rules. Use the map below for task-specific context.

## Project documents

| Working guide | Purpose / readers |
| --- | --- |
| [SRS](docs/SRS.md) | Final behavior, FR/NFR IDs, roles and exact keep/exclude matrix; everyone |
| [Team plan](docs/team-plan.md) | Member ownership, dependencies, P0-P7 tasks, prompting guidance and completion; everyone reads their sections |
| [Demo/manual UAT](docs/demo-manual.md) | Full manual acceptance and shorter panel sequence; everyone |
| [Architecture](docs/architecture.md) | Component/context/sequence diagrams, failure design and decision/divergence log |
| [Data model](docs/data-model.md) | ERD, identities, lifecycle, concurrency and migration constraints; data and consumers |
| [Integration contracts](docs/integrations.md) | Point register, component/repository/status semantics, scheduling and versioning; producers/consumers |
| [Engineering/security](docs/engineering.md) | Existing implementation patterns, UI conventions, test harness and boundary controls |

Architecture, persistence and integration contracts stay separate because they have different owners. They link to shared rules rather than redefine them. Original Submission 1 Parts A-F are separated below from project plans/evidence. AI assistance is allowed with disclosure in Step F; the quoted requirements remain distinct from our responses.

| Submission 1 | File |
| --- | --- |
| Step A — Working Integrated System | [Part A and delivery plan](docs/final-project/step-a-working-integrated-system.md) |
| Step B — Integration Documentation | [Part B and artifact/source reconciliation](docs/final-project/step-b-integration-documentation.md) |
| Step C — Performance, Reliability and Security Validation | [Part C, test catalogue/experiments and evidence](docs/final-project/step-c-performance-reliability-security-validation.md) |
| Step D — Trends Evaluation | [Part D and engineering decisions](docs/final-project/step-d-trends-evaluation.md) |
| Step E — Case-Based Lessons | [Part E and traceable design effects](docs/final-project/step-e-case-based-lessons.md) |
| Step F — Risk Closure and AI-Use Log | [Part F, RMMM and disclosure](docs/final-project/step-f-risk-closure-and-ai-use-log.md) |

## Run the current LMS on localhost

Tested on Windows with **PHP 8.3.30 and SQLite**. You do not need hosting, MySQL, Composer or Node for this baseline. Install PHP with the extensions listed in [the runtime manifest](runtime-requirements.json). If `php` is not recognized, add your PHP folder to PATH; Laragon users can use its terminal or the full path to `php.exe`.

To contribute without collaborator access, first click **Fork** on the team repository. In PowerShell, replace `YOUR-GITHUB-USERNAME` below with your own username, clone your fork and set the team remote. For a panel/read-only demo, clone the team URL instead and omit the remote-add line.

```powershell
git clone https://github.com/YOUR-GITHUB-USERNAME/lms-virustotal-integration.git
cd lms-virustotal-integration
git remote add upstream https://github.com/ja2gc/lms-virustotal-integration.git
php -v
php -m
New-Item -ItemType Directory -Force tmp/sessions | Out-Null
php -d session.save_path=tmp/sessions -S 127.0.0.1:8173 -t . router.local.php
```

Keep that terminal running and open **[http://127.0.0.1:8173](http://127.0.0.1:8173)**. The first visit opens the installer: choose your own local administrator account/password, install, then sign in. SQLite tables and initial settings are created automatically. No VirusTotal key is needed to try the existing LMS.

### Fictional localhost demo accounts

After installation, in another terminal at the project root run:

```powershell
php bin/seed-demo.php
```

| Role | Username | Local demo password |
| --- | --- | --- |
| Administrator | `panel_admin` | `DemoOnly!2026` |
| Teacher A | `panel_teacher` | `DemoOnly!2026` |
| Student A | `panel_student` | `DemoOnly!2026` |
| Teacher B / Student B (permission checks) | `panel_teacher_b` / `panel_student_b` | `DemoOnly!2026` |

These are deliberately public **fictional localhost credentials**, not provider keys or production passwords. The offline seeder creates two courses, enrollments, one text lesson, assignments and a two-card manual deck. Run it twice safely: existing/manual records and reviews are preserved. If a demo identity's password/role was changed or its name collides, seeding stops instead of resetting it. The installer account you chose remains separate. Keep the server bound to loopback.

Already cloned? Reuse that folder and follow the [fork synchronization rules](.agents/rules/workflow.md#fork-synchronization) to get the team main without losing your work; do not clone over it or add duplicate remotes blindly. Then create `tmp/sessions` and run the documented web-server command near the top. Seeding is optional. Stop the server with **Ctrl+C**; restart with the same command. Your local users/courses remain in the ignored SQLite database. Never delete `.installed` or the database just to restart. Use `127.0.0.1` consistently rather than switching hosts during a session.

In another terminal, run `php tests/run.php`. Optional developer UI logic check: `node tests/flashcard-study.test.cjs`. Step A lists remaining release gates.


## Setup troubleshooting

- **PHP not recognized:** use a terminal whose PATH contains PHP, or replace `php` with `& 'C:\path\to\php.exe'` in PowerShell. Do not copy the original upstream quick-start over this command.
- **Could not find driver:** enable `pdo_sqlite` in the PHP configuration actually used by the terminal, then restart PHP.
- **Session permission error or repeated sign-in:** create `tmp/sessions`, start from the repository root with the documented `-d session.save_path` argument, and keep the host/port consistent. The machine's original default session folder was not writable during the check.
- **Port already in use:** stop your earlier server with Ctrl+C or select an unused loopback port and open that exact URL. Do not terminate unrelated processes.
- **Private paths or arbitrary PHP return 404:** expected. Always start with `router.local.php`; a plain `php -S` command ignores `.htaccess` and can expose source files.
- **Stopped/restarted server:** reuse the same DB and `.installed` flag. Do not rerun installation/reset steps on an existing member's data. A disposable clean install belongs in a separate clone/folder.

## Current readiness

Installer, three role logins/navigation, course/enrollment, written submissions, grading/feedback, manual flashcards and repeatable fictional seeding have executed checks. [Step C](docs/final-project/step-c-performance-reliability-security-validation.md#baseline-verification) links actual evidence and limits.

P0b feature/menu/action cleanup is complete. P1a shared persistence, P1b private upload delivery, P2/P3 VirusTotal processing, P4 scan UI, P5 private HTTP access and P6/P7 integrated acceptance have separate statuses in the [team plan](docs/team-plan.md#completion-record). The localhost router denies old public assignment-upload URLs. P1b legacy import and P4 file-ID links are required before real attachment delivery is accepted. Use synthetic files and loopback only. A running baseline is not the final integrated release.

## Shared scan foundation

After installing the baseline, apply the repeatable SQLite scan schema explicitly:

```powershell
php bin/migrate.php
```

This creates shared revision/attempt tables. It does not import old attachments, implement upload storage or start a worker. Member 1 follows the P1b storage/transaction contract in the team plan and integrations guide; Members 2–4 consume the same repository. Tests use disposable fictional databases and private files:

```powershell
php tests/run.php
php tests/run.php --group=persistence
php tests/run.php --group=access
php tests/run.php --group=status
```

The coordinator's existing local database has not been migrated automatically. Current HTTP endpoints require the scan migration. Private byte storage defaults to `private/submissions`, or an external directory set through `SCAN_PRIVATE_DIR`; never use a public directory.

## Collaboration and schedule

Claim a numbered stream in the group chat. Members fork the team repository and clone their fork; collaborator access is not required. Push task branches to your fork and open PRs targeting this team's `main`. Follow the [fork synchronization rules](.agents/rules/workflow.md#fork-synchronization) before work and PR updates. Member 4 reviews producer/consumer contracts and overlaps before integration. Update task checks and completion in the team plan; Ready for review becomes Done only after reviewed/merged verification. Preserve others' changes, Doodle history/license and required AI disclosure.

Finish target: November 20, 2026. Working presentation/submission assumption: November 21, 2026; confirm against the class notice because the original correction still contained October 21 for the final date. Reserve rehearsal/evidence time before the panel.

## Source locations and provenance

`app/` holds PHP MVC code; `config/`, `database/` and `public/` hold configuration/schema/assets. `router.local.php` is the loopback router; `tests/` and `evidence/` hold checks/output. `bin/seed-demo.php` and `bin/migrate.php` exist; the scan worker remains planned. The runtime manifest is runtime-requirements.json.

Doodle is by Death Legion Team under the original [license](LICENSE); history and source notices remain. Inspected upstream revision: `6e5365ed49199bfceec61f9b3ba41d83a94ccb3d`. [Original upstream source/README](https://github.com/deathlegionteamlk/doodle/tree/6e5365ed49199bfceec61f9b3ba41d83a94ccb3d) is provenance, not this project's feature list/runbook.
