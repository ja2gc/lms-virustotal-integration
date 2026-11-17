# AI-use disclosure and review log

This final project permits AI assistance and requires disclosure. Human contributors retain responsibility for decisions, corrections, code review and evidence. Commit authorship and AI disclosure serve different purposes: no AI co-author trailers are required in human commits, while actual assistance is documented here.

## Actual assistance in this foundation session

| Date | Assistance | Human decisions / corrections | Verification and limitation |
| --- | --- | --- | --- |
| 2026-11-18 | Codex assistant read supplied Technical 2/3 text and Module 7 slide text; inspected selected NutriScope agent rules as reusable workflow guidance | Coordinator clarified final-project scope, four members, meaningful coding ownership, and separation of commit credit from disclosure | Earlier technical claims were not accepted as current evidence; original component files/transcript, T1/T4 and historical register remain unavailable |
| 2026-11-18 | Compared Doodle/Moodle candidates, downloaded Doodle source and searched for scan integration | Coordinator selected Doodle, one app plus background worker, and localhost only | Source search found no scanning integration; PHP syntax and controller-class checks recorded under `evidence/baseline/`; no app repairs or live scan |
| 2026-11-18 | Drafted architecture, contracts, ERD, security, test/release plans and team handoff foundation | Coordinator approved architectural direction; detailed numerical policies remain proposed implementation defaults | Source found fatal inherited-method conflicts; MIME validation helper exists but is not called on the submission path; grade action link mismatch recorded |
| 2026-11-18 | A read-only agent reviewed the design for material builder gaps | Coordinator approved the written design baseline before the implementation plan | Review identified ambiguous POST crash recovery and quota/polling-deadline interaction; durable start marker, deadline rules and IT06 were added. Review did not execute or certify product behavior. |
| 2026-11-18 | Compared upstream extras and technical requirements; revised scope, workflow and handoff contracts | Coordinator approved assignment-only assessments with retained LMS support; clarified that work should be divided by safe ownership/handoffs rather than contribution size | Documentation separates planned exclusions from actual source changes; shared repository, status JSON and rescan replay semantics were made explicit. Application code and PHP installation remain unchanged. |
| 2026-11-18 | Added a documentation navigation README, team/dependency/prompting guide and demo acceptance manual | Coordinator requested member-led planning rather than scripted conversations; demo scenarios should bound implementation and prevent scope growth | Optional openers give goals/boundaries, not follow-up scripts. DM01-DM12 map to tasks/requirements. Upstream README was preserved as reference; new implementation and demo outcomes remain unverified. |
| 2026-11-18 | Consolidated the scope baseline into an SRS and expanded the demo manual into full manual acceptance plus a shorter panel sequence | Coordinator approved an explicit SRS and requested coverage of retained LMS features, actual wrong behaviors and defects, not only the new integration | FR01-FR26, NFR01-NFR12 and preserved R01-R15 map to tasks/checks; UAT01-UAT34/variants include actual-result/defect/retest guidance. This was a documentation update; no UAT pass, code repair or runtime integration is claimed. |

## Corrections to AI assumptions

Local baseline follow-up (2026-11-18): the coordinator requested actual localhost access/setup instructions, then the sidebar highlight correction and a push to main. Codex reproduced the installer/controller/route faults, wrote failing regressions, repaired the minimum startup/rendering/URL paths, added a local router/runtime manifest and updated setup/status documentation. A fictional in-app browser journey verified installation, three role logins, course publication/enrollment, written submission, teacher grading and student feedback. Sidebar regression checks cover teacher/student/admin dashboard overlap. [Executed evidence](../evidence/baseline/localhost-smoke.json) distinguishes this from pending scan/upload/security/UAT work. The coordinator remains the human reviewer; final project acceptance is not claimed. No API key or real student data was used.

- Do not infer Moodle from a historical prompt; the selected baseline is Doodle.
- Do not label a CLI worker as a separately deployed microservice.
- Do not infer working upload protection merely from the presence of a MIME-check helper.
- Do not treat upstream README marketing/security claims as execution evidence.
- Do not treat the Module 7 lecture deck as a populated historical risk register.
- Do not treat prior prose stating tests passed as current reproducible results.
- Do not treat old reports/hash cache hits as a fresh teacher-requested scan.
- Do not treat GitHub Pages as a PHP runtime or introduce cloud deployment after the coordinator selected localhost.
- Do not add quizzes to the scope; assignment grading is separate. Retain approved course/calendar/announcement/notification support rather than stripping the LMS to an upload form.
- Do not optimize team assignments for one member's contribution size or split shared student/teacher templates and HTTP controller edits across competing owners.

## Continue this log during team implementation

For each substantive AI-assisted task record the contributor, tool/model if known, task/prompt summary, files/artifact affected, accepted suggestions, rejected/changed suggestions with reasons, human reviewer and actual verification evidence. Do not invent model/version, effort hours or results. Preserve relevant transcripts/unaltered original AI artifacts when the assessment requires them, with credentials/private data removed; add their actual locations once available.

The original Technical 3 AI artifact and corrected PHP prototype were not provided in this session. This log does not replace them. Complete the final record against the actual submission instructions and disclose unverified or inapplicable suggestions.
