# Demo manual and acceptance target

This manual describes the **target** demonstration, not verified current behavior. Baseline repairs and the VirusTotal integration are not implemented yet. Mark a scenario ready only after it runs on the integrated release; never present an expected result below as observed evidence.

The agreed product is an assignment-focused LMS, with the supporting features in [approved scope](feature-scope.md). This manual is a shared end goal: tasks should enable these scenarios or an explicit assessment/security requirement. Propose unrelated enhancements before implementing them. Successful presentation is necessary but does not replace the required load, reliability, security, risk and AI-use evidence.

## Setup and rehearsal checklist

- [ ] Use the actual verified commands in [local release instructions](release.md), on the same tagged/reviewed revision on all machines. Proposed commands are not yet verified.
- [ ] Prepare fictional Admin Demo, Teacher A, Teacher B, Student A and Student B accounts. Keep passwords local; do not put credentials in slides, source or this manual.
- [ ] Prepare a course named Systems Integration Lab, a short lesson/material, an assignment named Integration Reflection, a visible future deadline, and maximum score 100. Teacher B owns another course for access-control testing. Student A and Student B remain distinct owners.
- [ ] Prepare harmless synthetic `reflection-v1.txt` and `reflection-v2.txt` with different contents/hashes. No private coursework or real malware. Keep fixtures and any retained downloads in the agreed private/test locations.
- [ ] Use separate browser profiles/private sessions for roles; two ordinary tabs in one profile share login state. Keep navigation ready without exposing credentials or provider configuration.
- [ ] Prepare the worker and deterministic fixture modes for pending, each advisory, timeout/error/malformed response and recovery. Label fixtures visibly in the presentation; do not represent a crafted flagged response as real malware detection.
- [ ] Preserve sanitized evidence from a limited real VirusTotal request with matching analysis/file identity. A live call is useful if internet/quota permit, but long provider waits must not derail the narrative.
- [ ] Have a second member reproduce setup, verify private-path/role restrictions and restore a disposable DB/private-file copy. All members know the same file IDs, advisory labels and workflow.

## Main demonstration sequence

Use one connected story. Confirm the actual panel time allowance before fixing timings; roughly 6-8 minutes for the core teaching/submission/review story, followed by a short reliability/security/evidence segment, is a planning suggestion only.

| Scenario | Presenter action | Expected visible result / point to explain | Tasks / requirements |
| --- | --- | --- | --- |
| DM01: LMS context and administration | Briefly show fictional users/roles, a course and an announcement. Open a profile/theme only if useful; do not tour every setting. | This is an LMS supporting teachers and students; users have distinct roles. No external email or social messaging claim. | P0a/P0b/P4; R01/R15 |
| DM02: teacher publishes work | Teacher A opens the course, shows one material and creates/opens Integration Reflection with instructions, deadline and score limit. | Full teaching context is retained; assessments are assignments only. No quiz pages are shown. | P0a/P4; R15 |
| DM03: student finds the task | Student A opens/enrolls in the course, sees the material, assignment calendar/deadline and announcement. | The assignment is discoverable within the existing LMS. Do not invent a late-submission policy that was not implemented. | P0a/P0b/P4; R15 |
| DM04: save before scan | With worker stopped, submit a short response and reflection-v1.txt. | Submission saves and shows pending/checking separately from grading. The HTTP action did not wait for VirusTotal. | P1/P4/P5; R03/R06 |
| DM05: automatic analysis | Start the worker using the rehearsed fixture sequence; show current advisory update. Also show the sanitized real-provider trace, or one limited live request if practical. | Evidence belongs to this exact file revision. Explain the fixture/live distinction and that No Detections Reported is not a safety guarantee. | P2/P3/P4/P5; R02/R04 |
| DM06: teacher review and fresh rescan | Teacher A opens the submission, shows reason/counts/time and minimal history, then requests a rescan. | A new attempt/new provider analysis is requested; old evidence remains history. Repeated click/token does not create another job. Same reusable result component serves both workflows. | P1/P2/P3/P4/P5; R04/R06 |
| DM07: complete academic workflow | Teacher A downloads the authorized attachment, grades it (for example 85/100 as synthetic demo data) and writes feedback. Student A views the grade/feedback and in-app notification. | The integration fits the assignment workflow without replacing grading. Gradebook contains assignment results, not quiz features. | P0a/P4/P5; R05/R15 |

## Reliability, correctness and security segment

Prepare the following as short controlled scenarios. If time is limited, demonstrate one live fixture failure and show measured records for the others; the team still needs to execute all required assessment checks before submission.

| Scenario | Action | Expected proof | Tasks / requirements |
| --- | --- | --- | --- |
| DM08: replacement-file correctness | Put an older revision's scan in flight, then upload reflection-v2.txt and finish the old response. | Current attachment remains v2 with its own pending/result state; v1's result cannot overwrite it. | P1/P3/P4; R06 |
| DM09: dependency failure and recovery | Inject timeout, server error and malformed body in separate rehearsed checks; restore valid responses where retry policy permits. | Saved work remains. No false favorable result. Show waiting/Unresolved reason and actual recovery measurements; malformed evidence may need an explicit new attempt. | P2/P3/P5/P6; R03/R09 |
| DM10: access and scope | As Student B/Teacher B, try a known foreign result/download/rescan. Try an excluded quiz action directly. | Server denies unauthorized objects and excluded actions. A hidden button alone is not proof. | P0b/P5/P6; R05/R15 |
| DM11: advisory meanings | Show clearly labeled fixtures for malicious, suspicious-only, validated no-detection and inconclusive cases. | Flagged / Review Recommended / No Detections Reported / Unresolved are distinct, consistent outcomes. Do not call any file safe. | P2/P3/P4; R03/R04 |
| DM12: engineering evidence | Open the sanitized evidence summary: load ramp, failure/recovery results, boundary controls, two reusable consumers, trends/lessons, risks/AI-use and setup proof. | Present measured values from actual runs. Explain tested limits, unresolved risks and design effects. Never substitute this script for results. | P6/P7; R01/R07-R14 |

## Presenter handoffs

Suggested roles, adjustable by the team: UI owner guides DM01-DM04 and DM07; scanning owner explains DM05/DM11; HTTP/access owner explains DM06/DM10 and local setup; data owner explains DM08/DM09 and connects the evidence story. Practice handoffs on one coherent dataset. Everyone must understand the main workflow and one failure path, not just their page.

## If a live dependency is unavailable

Say what failed. Keep the saved submission visible, show its honest waiting/Unresolved state, switch to explicitly labeled fixture behavior, and show previously captured real-provider evidence if available. If the group has no real integration evidence, report that limitation; do not claim a simulated response demonstrates live API exchange. Do not show credentials, private logs, actual student files or provider account settings to fill waiting time.

## Definition of ready and stop rule

- [ ] DM01-DM12 executed on the integrated release, with pass/fail notes and real evidence linked. Any exception is explicit, not silently skipped.
- [ ] R01-R15 checked against code/evidence; all required Part A-F artifacts reflect the built system.
- [ ] Supporting retained features pass smoke checks; quizzes and other approved exclusions are absent from assessed navigation and denied by direct routes.
- [ ] Each member can explain their module, the input/output contract and one failure mode.
- [ ] Setup and rehearsal fit the panel slot; long quota/poll waits are supported by recorded measurements.

If a proposed feature does not serve a scenario here, a required assessment item or a concrete security/correctness control, propose it first and defer it unless approved. Internal implementation choices remain with each member and their agent; this manual is an acceptance goal, not a mandate to add new frameworks, dashboards or services.
