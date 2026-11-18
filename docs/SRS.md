# Software Requirements Specification

**Project:** LMS with VirusTotal Integration. Technical 3's original title is *Secure File Submission System with VirusTotal Integration*.

**Purpose:** define what the final system must do and the evidence needed to accept it. All members and agents must read this document before planning their part. It consolidates the approved requirements; it does not add quizzes, providers, cloud deployment or other new scope.

**Status:** target requirements, not implemented or verified behavior. Existing Doodle features must also be tested; upstream source presence alone is not acceptance. The [baseline audit](final-project/step-c-performance-reliability-security-validation.md#baseline-verification) records the checks actually executed.

## Authority

This file owns functional/nonfunctional requirements and the cleanup action matrix. Use [the root document map](../README.md#project-documents) to find design, implementation, verification and submission artifacts. Scope/contract changes require coordinator approval and corresponding checks; source/evidence establish current behavior.

## Terms and requirement levels

**Shall** denotes required final behavior. **Retain** means reuse and verify an approved upstream capability without expanding it. **New/strengthen** means group implementation is required. Performance numbers explicitly marked proposed are targets to validate, not measured results or fixed provider guarantees.

A submission is the academic record; a file revision is immutable attachment content; a scan attempt is one processing operation; an analysis ID is an external provider handle. Grade status, processing state and advisory status are distinct. Exact field names, nullability, UTC/seconds/bytes semantics and advisory classification are authoritative in [integration semantics](integrations.md#semantics-gate) and [the data model](data-model.md).

## Problem and minimum useful system

Teachers currently receive assignment attachments without an integrated security advisory. Extend the existing LMS so a saved file is analyzed through VirusTotal, the advisory is attached to the exact file version, and authorized users can see the outcome. An external outage must not lose the submission or imply that the file is safe.

Existing Doodle features are reused, not claimed as group-authored work. The group owns baseline repairs needed for this workflow and the integration, security controls, tests and evidence it adds.

## In scope

- Existing student, teacher and administrator accounts and course boundaries.
- Existing catalog/enrollment, basic course lessons/materials, role dashboards, assignment calendar, announcements, in-app notifications and basic profiles/theme as supporting LMS features; reuse and verify them without expanding them.
- Existing assignment submission and grading screens, with minimal security-status additions.
- Manual flashcard decks/cards and persisted recall review as an approved supporting study aid; no generated decks or graded quiz workflow.
- Durable file revisions, scan attempts, background processing, bounded retries, shared quota pacing, results and failure reasons.
- Automatic initial analysis and an authorized teacher request for a genuinely new analysis.
- Reusable result component used by automatic processing and teacher-requested processing.
- Protected attachment downloads and a local installation/demo another group member can reproduce.
- The final assessment's documentation, measurements, risk closure and AI-use disclosure.

No additional external API is committed to by the supplied Technical 2 and Technical 3. Internal UI routes, SQL and queue operations are integration points, not extra external providers. Doodle's optional OAuth, email, push and AI-study features are outside the assessed workflow; do not configure them merely because upstream includes them.

## Out of scope

Quizzes (creation, taking and auto-grading) and the excluded modules listed in [approved features](SRS.md#cleanup-action-matrix); custom malware engine; AI model training; cloud deployment; mobile application; redesign of the LMS; all upstream feature repairs; live malware testing; production use with real student work. Assignment grading/feedback remain required. The supplied six AI-project phases can guide problem/data/evaluation discipline, but do not introduce a model-development requirement.

## Actors and permission boundaries

| Actor | Allowed scope | Explicit restriction |
| --- | --- | --- |
| Student | Available catalog/enrollment/material workflow; own submissions, permitted own file/result, own grades/feedback and audience-appropriate notices | No other student's private work, teacher rescan/grading or admin controls |
| Teacher | Own courses/materials/assignments, their students' submissions, scan review/rescan/download and grading | No another teacher's private course submissions/actions unless separately authorized as admin |
| Administrator | Existing local user/role/course/settings/announcement administration and explicitly authorized support/review actions | Admin role does not expose credentials to browser output or remove private-file safeguards |
| Background worker | Due scan attempts, required private file bytes, metered provider calls and guarded result writes | No browser session dependency, uncontrolled retries or implicit authority from user-provided paths |

Catalog/course previews follow inspected existing visibility rules; this SRS does not invent a new public-sharing model. Authorization for private submissions, results, files and rescans must be checked on the server for each object.

## Functional requirements

Each row states final observable behavior and its acceptance check. Task P0 is split into P0a/P0b in the plan. Test IDs are defined in docs/final-project/step-c-performance-reliability-security-validation.md; DM IDs are in the demo manual. These mappings specify work/proof, not current pass results.

### Accounts and retained LMS support

| ID | Requirement and acceptance criterion | Change / tasks | Verification |
| --- | --- | --- | --- |
| FR01 | The system shall authenticate users, maintain role-appropriate sessions and allow logout. A student, teacher and admin can enter their permitted workflows; unauthenticated private actions are denied. | Retain/verify; P0a/P0b | BT01, ST01; DM01-DM04 |
| FR02 | Administrators shall manage fictional local users/roles and the existing basic course/settings/announcement workflow needed for the demo. A teacher/student cannot invoke admin-only actions directly. | Retain/verify; P0/P4/P5 | UI02, ST01; DM01 |
| FR03 | Teachers shall create/manage their own courses and basic lessons/materials. Students can discover available courses, enroll through the existing workflow and read authorized course content. Another teacher cannot manage private course objects. | Retain/verify; P0/P4/P5 | BT01, UI02, ST01; DM02-DM03 |
| FR04 | The system shall retain audience-appropriate announcements, assignment-related in-app notifications, role dashboards and the existing assignment calendar/deadlines. A graded assignment's student can find the resulting feedback/notification. | Retain/verify; P0/P4 | UI02, BT02; DM01-DM03/DM07 |
| FR05 | Users shall retain basic profile and existing theme behavior where working. No profile redesign, new customization workflow or additional account integration is required. | Retain/verify; P0/P4 | UI02; DM01 |

### Assignment and grading workflow

| ID | Requirement and acceptance criterion | Change / tasks | Verification |
| --- | --- | --- | --- |
| FR06 | An authorized teacher shall create/publish/manage an assignment in their course with instructions, a visible deadline and maximum score. Students see the correct course/assignment context. | Retain/verify; P0/P4/P5 | BT01, UI02, ST01; DM02-DM03 |
| FR07 | An enrolled student shall save a written submission without a file. The academic record persists and security processing displays Not applicable, without fabricating a favorable advisory or starting a file scan. | Retain/strengthen; P1/P4/P5 | IT01, UI01, UI02; DM04 |
| FR08 | An enrolled student shall submit a permitted file, with or without accompanying text. Reject invalid/failed uploads without reporting success; valid work is committed before provider analysis begins. Preserve useful form input on validation errors. | Strengthen; P1/P4/P5 | IT01, ST02, UI01; DM04 |
| FR09 | Where the assignment permits resubmission, replacement attachments shall create immutable revisions and a new applicable scan attempt. A failed replacement or text-only update must not silently lose the last committed attachment. | Strengthen; P1/P3/P4 | IT02, FT03; DM08 |
| FR10 | A teacher shall see submissions for their own assignments, including student/assignment context and current grade/security state as separate information. Another course's private submissions are denied. | Retain/strengthen; P0/P4/P5 | BT02, ST01, UI01; DM06 |
| FR11 | A teacher shall store assignment scores and feedback within the configured assignment scoring rules. The correct student sees them in the assignment/gradebook and existing in-app notification workflow; quiz results are not part of the assessed interface. | Retain/verify; P0/P4 | BT02, UI02; DM07 |

### Scanning and integration

| ID | Requirement and acceptance criterion | Change / tasks | Verification |
| --- | --- | --- | --- |
| FR12 | A committed valid attachment revision shall create durable automatic scan work without holding the submission HTTP request open for provider completion. Text-only submissions create no file-analysis job. | New; P1/P3 | IT01; DM04-DM05 |
| FR13 | The worker shall use VirusTotal to obtain a newly requested analysis for the exact file: documented lookup/re-analysis/upload paths return a persisted analysis ID. A historical hash report alone cannot be presented as a fresh scan. | New; P2/P3 | CT01, IT04, LIVE01; DM05-DM06 |
| FR14 | One published FileScanProvider result interface and unchanged adapter shall serve both automatic processing and teacher-requested processing through two real call sites. Consumers do not duplicate provider classification or require LMS sessions inside the component. | New; P2/P3 | CT04; DM06/DM12 |
| FR15 | The system shall validate analysis identity, completion evidence, counters and expected file hash before classification. Malformed, mismatched, stale or missing evidence cannot produce a favorable advisory. | New; P2/P3 | CT01-CT03, IT02; DM08/DM11 |
| FR16 | The component shall return the four exact advisory outcomes defined in Technical 3: Flagged, Review Recommended, No Detections Reported and Unresolved. Classification follows integrations.md; no outcome is labeled safe/clean/virus-free as an application conclusion. | New; P2/P3/P4 | CT03, UI01; DM11 |
| FR17 | Authorized users shall see their permitted current processing state, advisory/reason and checked time. Pending/retry/failed refresh states are understandable and distinct from grading. Status reads do not trigger provider requests. | New; P4/P5 | UI01, ST01; DM04-DM06 |
| FR18 | An owning teacher or admin shall request a fresh scan of the current attachment through a CSRF-protected authorized POST. A repeated request token returns its original attempt and actual state, not another job. Students/cross-course teachers/GET requests cannot rescan. | New; P1/P3/P4/P5 | CT04, IT03, ST01/ST02; DM06 |
| FR19 | Authorized teacher review shall expose minimal relevant scan history and current counts/reason/time. A pending rescan remains distinguishable from its previous completed result; no separate scan-analytics product is required. | New; P1/P4/P5 | IT02, UI01, ST01; DM06 |
| FR20 | Result writes shall remain attached to their own revision/attempt. A late old response, duplicate job or expired lease must not overwrite the current attachment's advisory. Identical hashes in different submissions remain separate records. | New; P1/P3 | IT02-IT04; DM08 |
| FR21 | The worker shall meter all provider requests against one configured shared budget, including identity reads and retries. Pending or retryable work is scheduled; authentication failures and quota exhaustion follow the documented pause/defer policy while submissions remain saveable. | New; P2/P3 | IT05, FT01/FT02; DM09 |
| FR22 | The worker shall resume replay-safe durable work after interruption, preserve stored analysis IDs, and avoid automatically repeating uncertain scan-start POSTs. Polling deadlines and bounded retries follow the integration contract; exhaustion remains Unresolved. | New; P1/P3 | IT04/IT06, FT03; DM09 |
| FR23 | Permitted users shall download attachments through an authorized private-file boundary. Known IDs or old static URLs must not bypass object checks; file bytes are delivered as attachments, not executed/inline previews. | Strengthen/new; P1/P5 | ST01-ST03; DM07/DM10 |
| FR24 | Scan status/rescan responses shall use the published versioned JSON names/types/nullability/error structure. They expose no private disk paths, keys, ownership internals or raw provider bodies. UI consumes that contract without reclassifying evidence. | New; P4/P5 | CT04, ST03, status/access checks; DM05-DM06 |

### Scope and demonstration behavior

| ID | Requirement and acceptance criterion | Change / tasks | Verification |
| --- | --- | --- | --- |
| FR25 | The assessed interface shall retain the approved supporting LMS features and offer assignments as its only assessment workflow. Quiz creation/taking/auto-grading and listed excluded modules are absent from navigation and denied through direct actions, including quiz actions on retained controllers. | Strengthen; P0b/P4/P5 | UI02, ST04; DM01-DM03/DM10 |
| FR26 | A local worker/test configuration shall support deterministic harmless provider fixtures for development/failure demonstrations, with explicit distinction from real provider evidence. The final artifact must also include proof of real LMS-to-VirusTotal exchange. | New; P2/P3/P6 | CT01-CT04, FT01-FT03, LIVE01; DM05/DM09/DM11-DM12 |
| FR27 | Authenticated users shall create manual question/answer decks, study permitted own/public/course decks, and persist recall ratings/next-review dates. Private decks are denied to unrelated users; course association requires course access. Deletion uses an authorized CSRF POST. Failed review requests do not advance visible progress. Generated decks remain excluded. | Retain/repair; P0/P4/P5 | FlashcardsTest.php, flashcard-study.test.cjs; UAT35a-e; DM13 |

## Nonfunctional requirements

| ID | Required quality / acceptance | Tasks and proof |
| --- | --- | --- |
| NFR01: authorization | Authenticate and authorize every private result, file, grading and rescan action by role/object scope; negative cross-user/course/direct-route cases are denied. | P0b/P5/P6; ST01/ST02/ST04; DM10 |
| NFR02: privacy/secrets | Use fictional users and synthetic attachments. Keep keys server-side and out of source/browser/logs/evidence; neither file nor hash queries may disclose confidential student content. | All streams; docs/engineering.md, ST03, LIVE01 preparation |
| NFR03: boundary validation | Validate upload errors/size/type/MIME, IDs and provider data; escape displayed values. Provider requests use fixed permitted origins and verified TLS; local HTTP is loopback only. | P0b/P1/P2/P5; CT02, ST02/ST03 |
| NFR04: durability | Provider timeout, error, quota pause, malformed evidence or worker failure shall not lose an accepted submission or invent a favorable result. Recorded saved counts/files remain consistent through the planned fault trials. | P1/P3/P6; FT01-FT03, IT01/IT02; DM09 |
| NFR05: consistency | Persist file/revision/attempt ownership and result/lifecycle transitions atomically where specified; use guarded claims/current pointers. No cross-submission or stale-result overwrite is accepted in concurrency tests. | P1/P3; IT02-IT04/IT06 |
| NFR06: bounded resource use | External requests have timeouts; retry/poll/lease budgets are bounded and metered. Unknown POST outcomes are not blindly replayed; no account multiplication or provider load test is used to bypass limits. | P2/P3/P6; IT04-IT06, FT02/FT03 |
| NFR07: measured performance | Report environment, increased workload, latency/throughput/errors/queue behavior, degradation point or tested bound, and failure mode. Separate application load tests with fixtures from provider capability. | P6; docs/final-project/step-c-performance-reliability-security-validation.md performance experiment; R08; DM12 |
| NFR08: usability | Retained/student/teacher workflows use consistent labels and navigation, clear loading/empty/error/Unresolved states, keyboard access, escaped long filenames and usable narrow/wide layouts. Do not rely on color alone. | P4; UI01/UI02; DM01-DM11 |
| NFR09: maintainability/compatibility | Reuse Doodle patterns and one provider-result contract. Publish stable interface/version behavior and field/ID/unit meanings; producer/consumer changes update contracts and checks together. | P1-P5; CT04, shared handoff checks; R04/R07 |
| NFR10: reproducibility/recovery | Provide the actual dependency manifest, safe example configuration, repeatable migrations/demo-data setup, app/worker commands and clean-machine instructions. Restore DB and private files together in a disposable test target. | P0/P1/P5-P7; BT01, RESTORE01, release evidence; DM12 |
| NFR11: evidence honesty | Preserve real commands/results, distinguish fixtures from live exchange, identify unresolved risks/failed or untested requirements, disclose actual AI use and retain upstream notices/history. Documentation must match the built release. | All streams/P6/P7; R01-R15 evidence; DM12 |
| NFR12: testability | Contract/failure checks and ordinary demo-data setup shall run offline with injected provider transport and fictional data. Tests must fail nonzero and must not depend on real API keys or disabled PHP assertions. | P0a/P1-P3/P6; tests/run.php harness, CT/FT catalogue |

### Targets and business rules not to invent

- The proposed local submission latency target is p95 <=2 seconds at 10 concurrent clients, excluding upload transfer, using the controlled fixture experiment in [Step C](final-project/step-c-performance-reliability-security-validation.md). It is not an achieved result or a provider completion guarantee; report actual measurements and agree any revision explicitly.
- File allowlist/size, request pacing, retry/poll/deadline defaults and test workload settings retain their proposed status in [engineering](engineering.md), [integrations](integrations.md) and [Step C](final-project/step-c-performance-reliability-security-validation.md) until validated in the common contract gate. An agent cannot silently substitute its own values.
- Preserve inspected existing assignment rules for deadlines, resubmission eligibility, score representation and enrollment/course visibility. If behavior is unclear or broken, record and resolve the specific rule with the coordinator before changing it; do not add a new late-submission, grading or sharing policy on assumption.
- PHP 8.3.30 is the tested baseline runtime. No upgrade, cloud runtime, new framework, malware engine or ML training pipeline is authorized by this SRS.

## Acceptance and traceability

FR/NFR rows identify expected product behavior, tasks, checks and demo scenarios. Assessment IDs R01-R15 are defined with their Step A-F files under final-project/. They are deliverable traceability, not another feature specification.

At release, record each FR/NFR's implemented source, executed test/evidence reference and pass/fail/not-tested status in the evidence manifest. Do the same for R01-R15 and DM01-DM13. A planned test or expected demo result is not a pass. Requirements are not complete merely because isolated modules pass; verify the integrated role workflow and real provider exchange. Any exception requires an explicit documented team decision and honest disclosure, not deletion of the requirement ID.

The full manual acceptance catalogue is UAT01-UAT35 (with separately recorded named variants) in [demo-manual.md](demo-manual.md). It covers retained LMS features as well as scanning and links each case to FR/NFR IDs. Its actual-result/defect log is a separate run artifact under `evidence/uat/` when executed. DM01-DM13 are the shorter presenter sequence referencing those cases. Manual acceptance does not replace the automated/measurement evidence required by the validation guide.

## Cleanup action matrix

| Treatment | Controllers/actions and visible entry points |
| --- | --- |
| Keep | Home, Auth login/logout/profile/theme; Admin users/roles/courses/categories/announcements/basic settings; Teacher courses/modules/materials/assignments/assignment grading; Student learning/submission/assignment grades; Course, Catalog, Assignment, Calendar, Notifications; manual Flashcard index/create/view/study/review/delete |
| Deny and remove links/panels | Quiz; Teacher quizzes/createQuiz/quiz/addQuestion/deleteQuestion; AI; Gamification; Message; Forum; Certificate; Poll; LearningPath; Whiteboard; Wiki; Group; Code; Analytics; Review; generic Api; OAuth/Push configuration |
| Deny within retained controller | Flashcard::generateFromCourse; keep its manual actions. Remove generated-deck promotions and AI links from retained screens. |
| Cover aliases too | flashcard/flashcards, message/messages, certificate/certificates, learningpath/learning_paths and all canonical controller names. An alternative spelling must not bypass the final action map. |
| Preserve shared internals | Existing schema history, enrollment/grade/notification helpers, and any still-called Gamification helper. The combined WikiGroupPushBackup file also contains Backup; do not delete the file wholesale. Existing setup/recovery utilities not listed for exclusion need an explicit decision before removal. |

P0b in the team plan applies this matrix to menus, promotions and direct actions/aliases. Test both intentional denial and retained workflows. Do not infer removal from an omitted chat mention or delete schema/data. Trace callers before deleting source: submission code still calls `Gamification::award(...)`, and shared helpers may remain behind disabled UI. No feature-flag framework is needed. Further scope changes require a proposal.
