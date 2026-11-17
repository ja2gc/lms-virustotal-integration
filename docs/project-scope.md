# Scope and acceptance criteria

## Problem and minimum useful system

Teachers currently receive assignment attachments without an integrated security advisory. Extend the existing LMS so a saved file is analyzed through VirusTotal, the advisory is attached to the exact file version, and authorized users can see the outcome. An external outage must not lose the submission or imply that the file is safe.

Existing Doodle features are reused, not claimed as group-authored work. The group owns baseline repairs needed for this workflow and the integration, security controls, tests and evidence it adds.

## In scope

- Existing student, teacher and administrator accounts and course boundaries.
- Existing catalog/enrollment, basic course lessons/materials, role dashboards, assignment calendar, announcements, in-app notifications and basic profiles/theme as supporting LMS features; reuse and verify them without expanding them.
- Existing assignment submission and grading screens, with minimal security-status additions.
- Durable file revisions, scan attempts, background processing, bounded retries, shared quota pacing, results and failure reasons.
- Automatic initial analysis and an authorized teacher request for a genuinely new analysis.
- Reusable result component used by automatic processing and teacher-requested processing.
- Protected attachment downloads and a local installation/demo another group member can reproduce.
- The final assessment's documentation, measurements, risk closure and AI-use disclosure.

No additional external API is committed to by the supplied Technical 2 and Technical 3. Internal UI routes, SQL and queue operations are integration points, not extra external providers. Doodle's optional OAuth, email, push and AI-study features are outside the assessed workflow; do not configure them merely because upstream includes them.

## Out of scope

Quizzes (creation, taking and auto-grading) and the excluded modules listed in [approved features](feature-scope.md); custom malware engine; AI model training; cloud deployment; mobile application; redesign of the LMS; all upstream feature repairs; live malware testing; production use with real student work. Assignment grading/feedback remain required. The supplied six AI-project phases can guide problem/data/evaluation discipline, but do not introduce a model-development requirement.

## Requirements and final submission mapping

| ID | Observable acceptance criterion | Final part | Design and required proof |
| --- | --- | --- | --- |
| R01 | Panel starts the app and worker from a clean local setup using documented dependencies and configuration | A | `release.md`; clean-machine setup record; dependency manifest and example configuration |
| R02 | LMS exchanges real data with VirusTotal and binds the returned analysis to the submitted file revision | A/B | `integrations.md`; limited live synthetic-file trace; fixture mode separately labeled |
| R03 | Submission survives provider timeout, error and malformed response; no favorable result is fabricated | C | `validation.md`; fault results and submission counts |
| R04 | Automatic and teacher-requested processing use the same unchanged result interface | B | Two real call sites, compatibility tests, published contract |
| R05 | Cross-user/cross-course result reads, downloads and rescans are denied | C | `security.md`; role and object-ownership tests |
| R06 | Replacement files and late results cannot overwrite the latest revision's advisory | B/C | `data-model.md`; concurrency, duplicate-job and stale-response tests |
| R07 | Every boundary lists protocol, format, authentication, synchronicity, errors, retry and versioning | B | `integrations.md`; reconcile with built code |
| R08 | Load test identifies environment, increased variable, measurements, degradation point and failure mode | C | Raw measurements and analysis; app capacity separated from provider quota |
| R09 | Fault injection measures recovery after timeout, error and malformed data | C | Timestamped state and recovery evidence |
| R10 | All five required trends have an adoption/rejection decision and engineering reason | D | `evaluation.md`; final implementation references |
| R11 | Two documented cases each cause a traceable design change | E | `evaluation.md`; decision, code and test links at release |
| R12 | All risks receive an evidence-backed final status; materialized risks compare prediction with outcome | F | `risks.md`; do not invent earlier predictions |
| R13 | Actual AI assistance and human review/corrections are disclosed | F | `ai-use.md`; transcript references |
| R14 | Corrected Technical 1 models and Technical 4 release work are reconciled with the system as built | B | Source artifacts still required; divergence log |
| R15 | Approved LMS support features remain usable; assignments are the only assessed activity; excluded menus/actions cannot be entered by direct URLs | A/B | `feature-scope.md`; UI02 and ST04; documented action allowlist |

## Source reconciliation

The supplied sources are Technical 2, Technical 3, Module 7 lecture slides and the pasted final Parts A-F. Technical 3 supersedes earlier result-classification and component-boundary proposals where it revises them. Its prototype execution was explicitly unverified. Technical 2's prose test claims are historical claims, not current raw evidence.

Not supplied: Technical 1 corrected models; Technical 4 artifact; the group's original populated RMMM register; original reusable PHP files and preserved AI transcript; full second-submission/live-session instructions. The coordinator gave November 20, 2026 as the ideal finish date; November 21 is the working presentation-date interpretation of a message that still said October 21. Confirm that final date against the class instructions. Reconcile missing sources before final submission. This foundation neither fabricates those artifacts nor claims their requirements are satisfied.

## Completion boundary for this session

Produce the shared README/documentation map, approved feature/workflow baseline, verified baseline findings, agent-readable contracts, four workstream/task boundaries, start/wait/handoff guide, optional prompting openers and demo manual/acceptance scenarios. Provide the four-person handoff summary in chat; names can be assigned by the team. Product implementation and final assessment validation happen in subsequent team work.
