# Demo manual and manual UAT

This manual serves two purposes: a **full manual acceptance/UAT checklist** for all approved main features, and a **shorter panel presentation sequence** referencing those same cases. It describes target behavior, not executed results. The integrated workflow, limited real provider exchange and browser recording rehearsal are verified in the validation guide. Full manual acceptance remains open. New test records start Not run; use actual evidence rather than copying expected outcomes.

The [SRS](SRS.md) is the requirements authority; the [feature overview](SRS.md#cleanup-action-matrix) explains the approved assignment-focused LMS. This manual is a shared end goal: build toward its scenarios and SRS requirements, not extra modules. Successful presentation does not replace load, reliability, security, risk and AI-use evidence. Team-run manual tests are rehearsal/acceptance evidence; do not claim independent stakeholder sign-off unless a real stakeholder reviews and accepts the results.

## Setup and rehearsal checklist

- [ ] Use the actual verified commands in [local release instructions](final-project/step-a-working-integrated-system.md#overview), on the same tagged/reviewed revision on all machines. The installer, migration, seeder and worker commands pass disposable setup checks.
- [ ] Prepare fictional Admin Demo, Teacher A, Teacher B, Student A and Student B accounts. Keep passwords local; do not put credentials in slides, source or this manual.
- [ ] Prepare a course named Systems Integration Lab, a short lesson/material, an assignment named Integration Reflection, a visible future deadline, and maximum score 100. Teacher B owns another course for access-control testing. Student A and Student B remain distinct owners.
- [ ] Prepare harmless synthetic `reflection-v1.txt` and `reflection-v2.txt` with different contents/hashes. No private coursework or real malware. Keep fixtures and any retained downloads in the agreed private/test locations.
- [ ] Use separate browser profiles/private sessions for roles; two ordinary tabs in one profile share login state. Keep navigation ready without exposing credentials or provider configuration.
- [ ] Prepare the worker and deterministic fixture modes for pending, each advisory, timeout/error/malformed response and recovery. Label fixtures visibly in the presentation; do not represent a crafted flagged response as real malware detection.
- [ ] Preserve sanitized evidence from a limited real VirusTotal request with matching analysis/file identity. A live call is useful if internet/quota permit, but long provider waits must not derail the narrative.
- [ ] Have a second member reproduce setup, verify private-path/role restrictions and restore a disposable DB/private-file copy. All members know the same file IDs, advisory labels and workflow.

## Full manual acceptance catalogue

Run this catalogue before the final rehearsal, not during the entire panel slot. It covers retained LMS support as well as the new integration. Check unlisted direct actions return 404. Use a new record per case/subcase and keep its actual result, evidence and defect link.

If a prerequisite is missing, mark Blocked with its owner/dependency; do not infer Pass. Some cases are operator-assisted (fixture injection, worker stop/restart, request counters or disposable restore). Use rehearsed local test controls/evidence, not a new debug UI or real malware. Live-provider proof and simulated failures must be recorded separately.

Case numbers group coverage rather than mandate execution order: establish clean setup (UAT31), then accounts/course/assignment prerequisites before dependent cases. Use separate disposable records for fault/replacement cases so they do not corrupt the presentation dataset. For the connected stage narrative, follow the DM sequence below. Capture results against the source revision actually tested and rerun affected cases after changes.

| Case | Actor / prerequisite | Actions | Expected acceptance result | SRS coverage |
| --- | --- | --- | --- | --- |
| UAT01 | Each fictional role; installed local app | Log in with valid credentials, open its dashboard, log out and revisit a private URL; also try an invalid login. | Correct role workflow is available; logout/invalid login do not authorize private actions. | FR01; NFR01 |
| UAT02 | Admin; disposable fictional test user | Create/update a fictional user's allowed account/role fields; log in as that user; try an admin URL as a student. | Saved account/role changes behave consistently; non-admin is denied. No destructive real-user changes. | FR02; NFR01 |
| UAT03 | Teacher A and Teacher B | Create/edit Teacher A's course details; reopen them; try managing that private course as Teacher B. | Own changes persist; foreign-course management is denied. | FR03; NFR01 |
| UAT04 | Student A; available course with known visibility/enrollment rules | Find course in catalog, enroll through the existing allowed flow, reopen enrolled course; check a course for which access is not allowed. | Catalog/enrollment and permitted content access work according to inspected rules; unauthorized private content is denied. | FR03; NFR01 |
| UAT05 | Teacher A; enrolled Student A | Create/edit a short text lesson and harmless course material through existing controls; view them as Student A. | Content/link/file and ordering/context are usable as authorized. This does not claim lesson files are part of assignment scanning. | FR03; NFR03/NFR08 |
| UAT06 | Admin; accounts in/out of a chosen announcement audience | Publish an announcement through existing controls; view it in the intended student/teacher audience and outside that audience. | Intended users see the correct content; audience restrictions hold. No external email/push is claimed. | FR02/FR04; NFR01 |
| UAT07 | Roles with seeded course/submission records | Open student, teacher and admin dashboards; follow their main links and compare displayed context/counts with prepared records. | Role-appropriate useful destinations/context remain; only supported destinations and accurate scan/grade labels. | FR04/FR25; NFR08 |
| UAT08 | Assignment with a known future deadline | View deadline on the assignment and existing calendar; change it as the authorized teacher and revisit both. | The same assignment/deadline is shown consistently in the intended local display; no invented late-submission policy. | FR04/FR06; NFR08 |
| UAT09 | Student A or Teacher A | Edit a supported basic profile field, save, reload and revisit another session as needed. | Own supported changes persist without exposing/editing another user's account. | FR05; NFR01/NFR08 |
| UAT10 | Logged-in account with existing theme switch | Toggle theme, navigate and reload; return to original preference. | Existing supported preference behavior works; no redesign or extra customization is required. | FR05; NFR08 |
| UAT11 | Teacher A; own course | Create/edit assignment instructions, deadline and maximum score; reopen as teacher and enrolled student. | Correct saved details/context are visible to the right roles; quiz creation is absent. | FR06/FR25 |
| UAT12 | Student A; assignment permits submission | Save a text-only response, reload and view as teacher. | Text persists on the correct record; no attachment scan starts; security is Not applicable with no fabricated favorable advisory. | FR07/FR12 |
| UAT13 | Student A; stopped worker; harmless permitted attachment | Submit text plus reflection-v1.txt; reload before starting the worker. | Submission/file remain saved and pending; save did not wait for a provider response; grading state remains separate. | FR08/FR12; NFR04 |
| UAT14 | Disposable test input; existing valid attachment | Separately test disallowed extension, oversized file, MIME mismatch and controlled upload-write failure. Record each variant. | No false success or unexpected revision; useful input/previous committed attachment is retained. Restore test setup afterward. | FR08; NFR03/NFR04 |
| UAT15 | Existing committed attachment; resubmission is allowed | Make a text-only update; then replace with reflection-v2.txt; separately simulate a failed replacement. | Text-only/failed replacement retains prior committed file; successful replacement has distinct immutable identity/new applicable attempt. | FR09; NFR04/NFR05 |
| UAT16 | Real permitted provider account; harmless synthetic file; available quota | Run one limited live analysis and verify its returned analysis/file relationship in sanitized evidence. | Real exchange and exact file association are proven. A fixture or old report alone cannot pass this live case. If unavailable, record Blocked/actual limitation. | FR13/FR26; NFR02/NFR11 |
| UAT17a-d | Clearly labeled provider fixtures | Run four distinct cases: malicious, suspicious-only, validated no-detection, and incomplete/inconclusive evidence; also include malformed/mismatched-evidence variants. | Respectively Flagged, Review Recommended, No Detections Reported and Unresolved; no favorable result from missing/wrong identity or invalid evidence. Record each subcase separately. | FR15/FR16/FR26; NFR03/NFR11 |
| UAT18 | Active and terminal attempts plus a failed-refresh fixture | Open authorized status, wait/refresh, hide/return to page, and simulate a transient refresh error. | Correct pending/retry/terminal labels; failure preserves last known result with an error; status reads do not start provider calls. | FR17/FR24; NFR06/NFR08 |
| UAT19 | Teacher A; submissions and multiple scan attempts | Open own submission list/detail; inspect current counts/reason/time and minimal history. | Correct student/assignment/current revision; old results remain history; pending rescan is distinguishable from previous result and grade. | FR10/FR19; NFR05/NFR08 |
| UAT20 | Teacher A/admin; current file; rehearsed duplicate-token test | Request a fresh rescan; repeat the same request token, including after its state advances. | One requested attempt/new analysis; repeated token returns the same attempt with actual state. Both automatic and rescan paths use the shared result component. | FR14/FR18/FR19; NFR09 |
| UAT21 | Teacher A; submitted assignment with max score 100 | Save a valid score/feedback (85/100 is optional synthetic data); reopen it; check the implemented score-bound rules. | Correct score/feedback persist within assignment rules; no new grading policy is assumed. | FR11 |
| UAT22 | Student A graded by UAT21; Student B distinct | Open Student A's assignment gradebook/feedback and resulting in-app notification, then check Student B's account. | Student A sees their own correct grade/feedback/notification; Student B does not receive/read it; no quiz result panels. | FR04/FR11/FR25; NFR01/NFR08 |
| UAT23 | Authorized student/teacher and private attachment | Download permitted file via application action; try missing file, guessed foreign file ID and old direct public path. | Correct permitted bytes delivered as attachment; missing/foreign/static bypasses denied appropriately, never server execution/inline preview. | FR23; NFR01/NFR03 |
| UAT24 | Old response held in fixture; replacement/current attempts | Submit new revision while old scan is pending; deliver old response later. Also test two submissions with identical bytes/hash. | New revision's current advisory remains correct; old result stays associated with its own attempt; identical content does not merge submissions. | FR09/FR20; NFR05 |
| UAT25a-c | Controlled timeout, server-error and malformed-response fixtures | Run each fault separately on saved work; observe displayed state/reason, restore valid responses where policy permits, record timing. | Work remains saved; waiting/Unresolved is honest; recovery/operator action is measured. Malformed evidence need not self-repair and can require a new authorized attempt. | FR21/FR22; NFR04/NFR06 |
| UAT26 | Student B, Teacher B and logged-out session; known foreign objects | Try foreign status, submission review, download, grade and rescan actions; test CSRF/GET rescan rejection using approved local checks. | Server enforces role/object/HTTP protections; hiding buttons is not the evidence. | FR02/FR10/FR18/FR23; NFR01 |
| UAT27 | Supported action list | Follow supported menus and check unlisted direct actions. | Supported workflows remain available; unlisted direct actions are denied. | FR25; NFR01 |
| UAT28a-b | Rehearsed local worker-crash fixtures and request log | Restart after a saved analysis ID; separately interrupt an in-flight start before local ID persistence. | Reads resume for saved IDs; ambiguous starts become Unresolved/start_outcome_unknown with no blind repeated POST. | FR22; NFR04/NFR05/NFR06 |
| UAT29 | Fixture clock/budget and recorded request counts | Simulate minute/day quota deferral, both quota-error categories and a wait beyond the persisted polling deadline. | Requests are paced/paused, submissions remain saveable, overdue polling ends Unresolved without another call. Do not exhaust a live account to test this. | FR21/FR22; NFR06 |
| UAT30 | Authorized synthetic status/rescan requests and response inspector | Compare pending, terminal, text-only, error and rescan replay responses with the published JSON examples; inspect allowed fields only. | Correct names/types/nulls/status/error meanings; no raw provider payload, disk path, key or ownership internals. UI does not reclassify counts. | FR17/FR24; NFR02/NFR03/NFR09 |
| UAT31 | Second member/machine or clean disposable target | Follow verified release commands, migrations and repeatable offline demo-data setup; start app and worker; run fixture checks. | Instructions work without the implementer's help; no network/key required for ordinary fixture/data setup; live mode remains distinct. | FR26; NFR10/NFR12 |
| UAT32 | Disposable matched DB/private-file backup | Restore only into a temporary target, open relevant record and compare harmless attachment bytes/hash/linkage. | Restored DB and files agree; backup presence alone is not treated as proof; original demo target is untouched. | NFR10 |
| UAT33 | Retained and scan screens with long labels/filenames and empty/error records | Test keyboard navigation, narrow/wide layouts, labels, loading/empty/read-only/error states and status without color alone. | Main actions remain usable and understandable; no clipping, lost useful input or unsafe displayed text. | NFR08; FR08/FR17 |
| UAT34 | Integrated release and actual evidence manifest | Review tests, both real reusable consumers, load/fault/security results, setup/restore proof, trends/case effects, risks and actual AI-use disclosure. | Source/evidence map to FR/NFR/R IDs; measured values and limitations are honest. Manual inspection does not replace automated contract/concurrency/load proof. | NFR07/NFR09/NFR11/NFR12; R01-R15 |
| UAT35a-e | Teacher/student and a two-card manual deck; unrelated user for negative checks | (a) Create a manual deck with complete cards; reject empty/incomplete input. (b) Open/study an own/public/permitted course deck; deny another private deck and foreign course association. (c) Flip by mouse/keyboard and rate both cards. (d) Reload/reopen; saved reviews are not due again immediately. Test failed review/repeated clicks without false progress. (e) Reject GET/no-CSRF deletion; permit owner/admin POST. | Cards and review schedule persist, private/course boundaries hold, failed saves preserve progress. No quiz grade or generated-deck claim. | FR27; NFR01/NFR03/NFR08 |

## Recording actual results and defects

For each case/subcase, record the following in a sanitized run log under `evidence/uat/` **when testing actually occurs**. Do not create prefilled pass records now.

- Case/subcase ID and relevant requirement IDs.
- Tester, run date/time, source revision, environment/browser, and mode (fixture/live/operator-assisted).
- Preconditions/data used and any departure from the case steps.
- Expected result reference, actual observed result and status: **Not run**, **Pass**, **Fail**, or **Blocked**.
- Screenshot/log/measurement reference, defect ID if applicable, responsible workstream and retest reference.

Use a defect record with reproducible steps, expected versus actual behavior, affected requirement/case, severity and evidence. Suggested severity meanings: blocker prevents a required end-to-end workflow or exposes/loses data; major fails a required feature/control; minor is a limited presentation/usability defect. These are triage categories, not permission to accept a failure silently.

After a fix, retain the original failed record and add a retest result on the new revision. Rerun related role/dependency cases and automated checks, not only the single screen. For example a rescan fix can affect automatic scanning, history, current-pointer selection and status JSON. Record real stakeholder acceptance separately from team testing if the instructor/client provides it.

## Panel sequence mapped to manual acceptance

The full UAT run can be long. The final presentation is a selective connected narrative using already-tested cases; it need not execute every negative test on stage. Supporting LMS features are covered in UAT even when only shown briefly during the presentation.

| Panel scenario | Reuse these manual cases |
| --- | --- |
| DM01: context/admin/support | UAT01/UAT02/UAT06/UAT07/UAT09/UAT10 |
| DM02: teacher publishes work | UAT03/UAT05/UAT11 |
| DM03: student finds work/deadline | UAT04/UAT08 |
| DM04: save before scanning | UAT12/UAT13 |
| DM05: automatic analysis | UAT16/UAT17a-d/UAT18 |
| DM06: review and new scan | UAT19/UAT20 |
| DM07: grading, feedback, notification, download | UAT21/UAT22/UAT23 |
| DM08: replacement correctness | UAT15/UAT24 |
| DM09: failure/recovery, with other cases shown as evidence | UAT14/UAT25a-c/UAT28a-b/UAT29 |
| DM10: access boundaries | UAT26/UAT27 |
| DM11: four advisory meanings | UAT17a-d |
| DM12: engineering evidence | UAT30/UAT31/UAT32/UAT33/UAT34 |

## Main demonstration sequence

Use one connected story. Confirm the actual panel time allowance before fixing timings; roughly 6-8 minutes for the core teaching/submission/review story, followed by a short reliability/security/evidence segment, is a planning suggestion only.

| Scenario | Presenter action | Expected visible result / point to explain | Tasks / requirements |
| --- | --- | --- | --- |
| DM01: LMS context and administration | Briefly show fictional users/roles, a course and an announcement. Open a profile/theme only if useful; do not tour every setting. | This is an LMS supporting teachers and students; users have distinct roles. No external email or social messaging claim. | P0a/P0b/P4; R01/R15 |
| DM02: teacher publishes work | Teacher A opens the course, shows one material and creates/opens Integration Reflection with instructions, deadline and score limit. | Full teaching context is retained; assessments are assignments only. Assignments provide the assessment workflow. | P0a/P4; R15 |
| DM03: student finds the task | Student A opens/enrolls in the course, sees the material, assignment calendar/deadline and announcement. | The assignment is discoverable within the existing LMS. Do not invent a late-submission policy that was not implemented. | P0a/P0b/P4; R15 |
| DM04: save before scan | With worker stopped, submit a short response and reflection-v1.txt. | Submission saves and shows pending/checking separately from grading. The HTTP action did not wait for VirusTotal. | P1/P4/P5; R03/R06 |
| DM05: automatic analysis | Start the worker using the rehearsed fixture sequence; show current advisory update. Also show the sanitized real-provider trace, or one limited live request if practical. | Evidence belongs to this exact file revision. Explain the fixture/live distinction and that No Detections Reported is not a safety guarantee. | P2/P3/P4/P5; R02/R04 |
| DM06: teacher review and fresh rescan | Teacher A opens the submission, shows reason/counts/time and minimal history, then requests a rescan. | A new attempt/new provider analysis is requested; old evidence remains history. Repeated click/token does not create another job. Same reusable result component serves both workflows. | P1/P2/P3/P4/P5; R04/R06 |
| DM07: complete academic workflow | Teacher A downloads the authorized attachment, grades it (for example 85/100 as synthetic demo data) and writes feedback. Student A views the grade/feedback and in-app notification. | The integration fits the assignment workflow without replacing grading. Gradebook contains assignment results. | P0a/P4/P5; R05/R15 |

## Reliability, correctness and security segment

Prepare the following as short controlled scenarios. If time is limited, demonstrate one live fixture failure and show measured records for the others; the team still needs to execute all required assessment checks before submission.

| Scenario | Action | Expected proof | Tasks / requirements |
| --- | --- | --- | --- |
| DM08: replacement-file correctness | Put an older revision's scan in flight, then upload reflection-v2.txt and finish the old response. | Current attachment remains v2 with its own pending/result state; v1's result cannot overwrite it. | P1/P3/P4; R06 |
| DM09: dependency failure and recovery | Inject timeout, server error and malformed body in separate rehearsed checks; restore valid responses where retry policy permits. | Saved work remains. No false favorable result. Show waiting/Unresolved reason and actual recovery measurements; malformed evidence may need an explicit new attempt. | P2/P3/P5/P6; R03/R09 |
| DM10: access and scope | As Student B/Teacher B, try a known foreign result/download/rescan. Try an unlisted action directly. | Server denies unauthorized objects and unlisted actions. A hidden button alone is not proof. | P0b/P5/P6; R05/R15 |
| DM11: advisory meanings | Show clearly labeled fixtures for malicious, suspicious-only, validated no-detection and inconclusive cases. | Flagged / Review Recommended / No Detections Reported / Unresolved are distinct, consistent outcomes. Do not call any file safe. | P2/P3/P4; R03/R04 |
| DM12: engineering evidence | Open the sanitized evidence summary: load ramp, failure/recovery results, boundary controls, two reusable consumers, trends/lessons, risks/AI-use and setup proof. | Present measured values from actual runs. Explain tested limits, unresolved risks and design effects. Never substitute this script for results. | P6/P7; R01/R07-R14 |

## Presenter handoffs

**DM13: manual flashcards (short supporting feature).** Use the seeded Panel Demo Integration Cards. Open Flashcards, show two manual cards, start studying, flip/rate and show completion; reopen to establish saved progress. Link to UAT35a-e/FR27 and the flashcard test evidence. Budget **45–60 seconds**, a rehearsal target rather than a measured human presentation time. The initial automated create-and-two-card trial took 28 seconds. Pre-create a fresh small deck for rehearsal if the seeded cards are no longer due; normal seeding deliberately does not reset reviews. The cards are manually authored. Every main feature is covered in UAT; the short panel sequence need not run every negative variant live.

Suggested roles, adjustable by the team: UI owner guides DM01-DM04 and DM07; scanning owner explains DM05/DM11; HTTP/access owner explains DM06/DM10 and local setup; data owner explains DM08/DM09 and connects the evidence story. Practice handoffs on one coherent dataset. Everyone must understand the main workflow and one failure path, not just their page.

## If a live dependency is unavailable

Say what failed. Keep the saved submission visible, show its honest waiting/Unresolved state, switch to explicitly labeled fixture behavior, and show previously captured real-provider evidence if available. If the group has no real integration evidence, report that limitation; do not claim a simulated response demonstrates live API exchange. Do not show credentials, private logs, actual student files or provider account settings to fill waiting time.

## Definition of ready and stop rule

- [ ] DM01-DM13 executed on the integrated release, with pass/fail notes and real evidence linked. Any exception is explicit, not silently skipped.
- [ ] UAT01-UAT35 and named subcases executed or explicitly reported Blocked/Fail, with actual observations, evidence, defects and retests. No unresolved blocking/required-feature failure is silently called ready.
- [ ] R01-R15 checked against code/evidence; all required Part A-F artifacts reflect the built system.
- [ ] Every SRS FR/NFR has a trace to the relevant manual and/or automated evidence. Passing a panel narrative or a manual case does not by itself prove load limits, internal contract reuse or concurrency guarantees.
- [ ] Supporting LMS features pass smoke checks; the supported action map is enforced by navigation and direct routes.
- [ ] Each member can explain their module, the input/output contract and one failure mode.
- [ ] Setup and rehearsal fit the panel slot; long quota/poll waits are supported by recorded measurements.

If a proposed feature does not serve a scenario here, a required assessment item or a concrete security/correctness control, propose it first and defer it unless approved. Internal implementation choices remain with each member and their agent; this manual is an acceptance goal, not a mandate to add new frameworks, dashboards or services.
