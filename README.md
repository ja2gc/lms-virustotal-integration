# LMS with VirusTotal Integration

An assignment-focused LMS extending [Doodle](https://github.com/deathlegionteamlk/doodle). Students submit work; teachers receive file-security advisories, can request a fresh scan, and grade/give feedback. Useful course, calendar, announcement and in-app notification features remain. **Quizzes are outside the approved scope.**

Technical 3's original title is *Secure File Submission System with VirusTotal Integration*. Team repository: [ja2gc/lms-virustotal-integration](https://github.com/ja2gc/lms-virustotal-integration).

## Read this before starting

1. [Documentation map](docs/README.md): what every guide is for and what your role must read.
2. [Approved scope and user workflow](docs/feature-scope.md): what we keep, exclude and add; do not expand or remove features independently.
3. [Demo manual and acceptance target](docs/demo-manual.md): the end-to-end behavior we must successfully demonstrate.
4. [Team guide](docs/team-guide.md): four workstreams, file ownership, what can start now, dependencies, handoffs and prompting advice.
5. [Implementation plan](docs/implementation-plan.md): your assigned task IDs, interfaces, files and checks. Read relevant linked contracts before coding.

Every member should understand this reading order. Every agent must also start with [AGENTS.md](AGENTS.md) and its linked workflow rules. The coordinator assigns actual people to the four workstreams; the repository does not assume a particular member needs a particular amount of code.

## Current state: foundation, not a finished application

Doodle source, project guides, diagrams, shared contracts, task/handoff plan and demo target are present. No application repair, module removal, VirusTotal feature, migration or worker has been implemented by the foundation session. [Recorded baseline checks](docs/baseline-audit.md) found controller-loading failures and other blockers. Passing individual PHP syntax checks did not establish a working website.

Begin with P0a's baseline repair gate. Do not claim a running integration from documentation or fixture output. The [release guide](docs/release.md) contains the local setup contract to implement and verify; it is not yet a panel-ready runbook. The [validation guide](docs/validation.md) defines the evidence still needed.

## Shared technical direction

- One PHP application and a CLI background worker from the same codebase, with SQLite and private local files.
- Localhost demonstration; no cloud deployment, new frontend framework or broker is required.
- VirusTotal is the assessed external service. Browser requests go through the LMS; provider keys stay server-side.
- Submission/grade state, processing state and security advisory have different meanings. Use the shared [integration contract](docs/integrations.md) and [data model/ERD](docs/data-model.md).
- Use fictional accounts and synthetic attachments; never commit credentials, local databases, actual student files or backups.
- PHP 8.3.30 is the runtime inspected so far. No runtime upgrade was performed; freeze the tested team environment during the baseline gate.

## Repository navigation

| Location | Purpose / status |
| --- | --- |
| `app/core/`, `app/controllers/`, `app/views/`, `app/helpers/` | Existing Doodle authentication/routing/controllers/screens/helpers; inspect before extending |
| `config/`, `database/`, `public/` | Existing configuration/schema/assets; existing public submission storage must be strengthened |
| `docs/` | Authoritative project scope, architecture, contracts, tasks, security, evidence plan and demo guides |
| `evidence/baseline/` | Actual normalized baseline check results; later evidence folders are created when real checks run |
| `AGENTS.md`, `.agents/rules/workflow.md` | Agent reading order, boundaries and verification rules |
| `app/scan/`, `bin/`, `tests/`, `database/migrations/` | Planned integration areas; the task plan specifies their creation, not existing implemented behavior |

## Collaboration and completion

One primary owner per file/stream. Merge the baseline sweep before feature branches change shared controllers; merge contracts before consumers connect. Use task branches and reviewed handoffs with exact tests and known limitations. Every member supplies their own verification/evidence. Complete the demo scenarios **and** the final assessment's measured tests/documentation; neither alone is sufficient.

Finish target is November 20, 2026. See the approved scope for the presentation-date assumption and rehearsal sequence. Further scope changes need a proposal before editing. AI assistance is permitted, with the required [AI-use disclosure](docs/ai-use.md); human commits use the actual contributor's identity without AI co-author trailers.

## Upstream provenance

Doodle is by Death Legion Team under the original [license](LICENSE). Its source history and notices are retained. The [upstream README reference](docs/upstream-doodle.md) describes additional upstream features and unverified setup/security claims; it is not this project's feature list or runbook.
