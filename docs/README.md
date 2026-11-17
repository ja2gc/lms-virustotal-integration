# Documentation map: every member starts here

This repository is the planning/source foundation for **LMS with VirusTotal Integration**. Doodle provides existing LMS code; the group builds and proves the assignment-security integration. Implementation has not been completed. Read the [root README](../README.md) for current status and repository locations.

## Shared must-reads, in order

| Read | Why every member needs it |
| --- | --- |
| [AGENTS.md](../AGENTS.md) and [workflow rules](../.agents/rules/workflow.md) | Required agent context, reuse/security/verification rules and scope authority |
| [Software Requirements Specification](SRS.md) | Authoritative functional requirements by role, nonfunctional requirements, acceptance criteria and R01-R15 deliverable mapping |
| [Approved features and user workflow](feature-scope.md) | What remains, what is excluded, and the assignment-only assessment decision |
| [Demo and manual UAT guide](demo-manual.md) | Full-feature manual checks, actual-result/defect recording, and a short panel script referencing those same cases |
| [Team guide](team-guide.md) | Which stream owns each area, what can start now, what must wait, and how to prompt your own agent |
| [Baseline audit](baseline-audit.md) | Real upstream blockers and limits of the checks already executed; prevents starting from a false working-system assumption |
| [Implementation plan](implementation-plan.md) | Read the dependency/ownership sections and your own task details, rather than executing the entire plan |

## Then read for your workstream

| Workstream | Additional must-reads | Task IDs |
| --- | --- | --- |
| Foundation/data | [Architecture](architecture.md), [data model](data-model.md), [integration/repository contracts](integrations.md), [security](security.md) | P0a, P1 |
| Scanning engine | [Architecture](architecture.md), [integration contracts](integrations.md), [data model](data-model.md), [validation](validation.md), [security](security.md) | P2, P3 |
| LMS interface | [Development/UI](development.md), [integration JSON/status meanings](integrations.md), [security](security.md) | P4 |
| HTTP/access/local delivery | [Integration contracts](integrations.md), [data model](data-model.md), [security](security.md), [release/local setup](release.md) | P0b, P5 |

Everyone participates in P6/P7 integrated evidence and rehearsal. You do not need to master every implementation detail before starting your own stream, but you must understand its producer/consumer interfaces. No one should wait idle when the team guide lists independent preparation/isolation work.

## Purpose of every project guide

| Guide | Authoritative purpose | Read especially when |
| --- | --- | --- |
| [SRS](SRS.md) | Authoritative FR/NFR requirements, R01-R15 final Parts A-F mapping and missing historical artifacts | Every member/agent before planning; final completeness |
| [Feature overview](feature-scope.md) | Readable approved-feature/workflow companion to the SRS, including exclusions and date assumptions | Understanding pages, navigation and user journeys |
| [Architecture](architecture.md) | System boundaries, diagrams, failure flow, decisions and divergences | Changing responsibility or shared behavior |
| [Data model](data-model.md) | ERD, field meanings, ownership, state transitions, concurrency and migration rules | Reading/writing submission/file/attempt data |
| [Integrations](integrations.md) | Complete boundary register, reusable interface, repository methods, JSON, semantics and retry/quota policy | Connecting modules or changing APIs/fields/statuses |
| [Development](development.md) | Existing code locations, reusable UI patterns, screen states and collaboration conventions | Working on controllers, screens or shared helpers |
| [Security](security.md) | Controls per integration point, private files, authorization, secrets and local serving | Any boundary, upload, download, result or rescan change |
| [Implementation plan](implementation-plan.md) | Task files/interfaces/tests, ownership boundaries and build order | Planning or implementing the assigned work |
| [Team guide](team-guide.md) | Four workstreams, start/wait rules, safe handoffs and lightweight prompting help | Coordinating with classmates/agents |
| [Validation](validation.md) | Test IDs, load/fault/recovery/security experiments and required raw evidence | Proving behavior and preparing final Part C |
| [Release](release.md) | Local dependency/configuration/setup contract, migration/recovery/release gates | Making another machine/panel run the system |
| [Demo and manual UAT guide](demo-manual.md) | Full manual cases and traceability, DM01-DM12 presenter sequence, pass/fail/defect recording | Manual acceptance, regression, scope control or rehearsal |
| [Evaluation](evaluation.md) | Trends decisions and two sourced cases with traceable design effects | Preparing final Parts D/E |
| [Risks](risks.md) | RMMM baseline, indicators, contingencies and closure format | Updating actual risks and final Part F |
| [AI-use](ai-use.md) | Actual AI assistance, human decisions, corrections and disclosure | Recording AI-assisted work/final Part F |
| [Baseline audit](baseline-audit.md) | Inspected source provenance, reproduced failures and unverified areas | Repairing upstream issues or interpreting old claims |
| [Upstream Doodle reference](upstream-doodle.md) | Original product README retained for provenance; not our scope or verified instructions | Looking up original behavior/credits only |
| [Old scope link](project-scope.md) | Redirect to the expanded SRS; no independent requirements | Following an earlier shared link only |

## How to interpret status and evidence

Guides specify the target unless they explicitly cite executed evidence. Existing app source does not prove the screen works; an API description does not prove a real request; a fixture pass does not prove provider availability. [Baseline JSON](../evidence/baseline/php-checks.json) records actual checks and failures. Future evidence is added when real tests run.

Preserve one authoritative guide per topic. Ask its owner/coordinator before changing a shared contract or approved scope. Keep task-to-person names in team communication; numbered workstreams are defined in the team guide. Use a single reviewed handoff instead of copying conflicting implementations between branches.

Technical 3's original project title was *Secure File Submission System with VirusTotal Integration*. The agreed target is Doodle, one application plus background worker, SQLite and localhost, with assignment-focused assessment and retained useful LMS support. Detailed numerical defaults remain implementation hypotheses to validate. T1/T4, the original populated RMMM and original reusable-component/transcript artifacts still need reconciliation before final submission.
