# Step F — Risk Closure and AI-Use Log

## Original submission instruction — Part F

> Update the RMMM register from Module 7 with the final status of every risk: materialized, mitigated, still open, or closed. For any risk that materialised, state what actually happened against what you predicted.

## Project response

## Risk register

The supplied Module 7 slides describe risk identification, likelihood/impact scoring, response, monitoring, owners and dates. They are not the group's completed historical register. This is a **new planning baseline**; merge the original register if provided, retain its IDs/predictions and map duplicates rather than rewriting history.

## Review process and scoring

Review at kickoff, each merge affecting a boundary, and before the final demo. Every member reports new risks with a concrete cause/consequence. At kickoff assign a member number and calendar due date to each row; role owners and delivery gates below are provisional. Set likelihood and impact on defined 1-5 scales using project evidence; priority is their product. Numeric values are intentionally unscored until the team supplies project evidence and agrees impact thresholds. The finish target is November 20; see README.md for the presentation-date assumption. Do not let AI-generated category assumptions masquerade as observed likelihood.

Likelihood scale: 1 rare/no local indication; 2 plausible with strong prevention; 3 credible with limited evidence; 4 recurring observed precursor; 5 happening or consistently reproducible. Impact: 1 minor inconvenience; 2 local repair under an agreed work session; 3 feature/rehearsal delay; 4 blocks required evidence or core workflow; 5 loss/exposure of data or inability to present the required integrated system. Refine effort boundaries with the actual schedule before scoring.

## Register

| ID / category | Risk and evidence | Provisional owner / due gate | Mitigation | Indicator and trigger | Contingency | Current status |
| --- | --- | --- | --- | --- | --- | --- |
| RK01 product/project | Doodle core pages fail; nine controller files fail class-load checks | Baseline repair owner / before feature merges | Fix common rendering conflict, bootstrap and grade route; regression checks | Any scoped controller/installer/smoke check fails | Repair scoped blockers; reassess baseline if repairs exceed agreed time budget | Materialized; baseline faults repaired/regression-tested, no earlier prediction claimed |
| RK02 external/integration | VT minute/day quota limits scanning throughput | Worker owner / before live test | Shared persisted budget, slow polling, limited synthetic smoke checks | 429 or pending age beyond measured expected bound | Defer work; show fixture paths and prior labeled live evidence; do not claim fixture as live | Still open |
| RK03 external | Provider timeout or account-key failure | Adapter owner / before integration acceptance | Timeouts, normalized reasons, bounded read retry, provider pause | Repeated transient failure or one auth rejection | Preserve submissions; correct account configuration and resume reads | Still open; injected failures are planned |
| RK04 security/privacy | Files/indicators may be shared externally; upstream attachments have public URLs | File-access owner / before uploads/live requests | Synthetic-only policy, private storage and authorization, no sensitive indicators | Any real/private file proposed or unauthorized static URL works | Stop external requests/exposure; remove access path; document actual event if it occurs | Still open |
| RK05 product | Old result overwrites new file or duplicate job corrupts state | Persistence owner / before worker merge | Revision/attempt identity, lease-token writes and unique request keys | IT02/IT03 failure | Pause worker and repair state-selection bug before demo | Still open |
| RK06 project | Contract drift and competing edits across four members | Integration coordinator / before dependent merges | One owner per file/stream; P0a then P0b/P1 handoffs; shared repository/JSON fixtures and producer-consumer checks | Incompatible keys/status meanings, wrong ID/units, or simultaneous shared-file edits | Resolve producer contract first, rebase consumers after merge, rerun full vertical slice | Still open; design controls documented, implementation not proven |
| RK07 project/evidence | T1/T4/historical register/original component/transcript absent; final presentation date still needs class-notice check | Integration coordinator / before documentation freeze | Reconcile missing sources and final date; protect Nov 20 evidence/rehearsal time | Required source/date unresolved at rehearsal | Disclose gap and clarify with team/instructor; do not invent evidence | Still open |
| RK08 product | Conservative classifier often returns Unresolved for unsupported engines | Component owner / before acceptance | Exercise real synthetic sample and all engine-category fixtures; show reasons/counts | Non-detection cases mostly inconclusive | Review policy explicitly with team; record semantic/versioning change before altering it | Still open |
| RK09 project/operations | Local setup or internet fails on finals day | Demo coordinator / rehearsal before submission | Second-machine setup, offline fixtures, preserved sanitized live evidence, restore test | Rehearsal cannot reproduce or live provider unavailable | Use clearly labeled deterministic demo plus actual prior live evidence; report live outage honestly | Still open |
| RK10 project/scope | Optional LMS repairs or runtime upgrade displace required assignment integration before Nov 20 | Scope coordinator / each proposed scope change | Freeze approved assignment-only assessment; retain useful existing support; no unapproved removals/new modules; use one tested runtime | Work starts on quizzes/excluded modules, or a retained feature/runtime change needs substantial new work | Stop optional work; agree smallest repair/deferment; keep required tests, integration and rehearsal | Still open |

## Final closure format

For each risk append final status exactly `materialized`, `mitigated`, `still open` or `closed`; original prediction; what actually occurred; response taken; residual exposure; evidence reference; reviewing member number and review date. A passing fixture test mitigates a path but does not close external provider availability. New risks need a real prediction recorded before the outcome; do not backdate this baseline or claim RK01 was predicted before inspection.

## AI-use log

AI assistance is permitted for this final artifact with disclosure. Human contributors own decisions, review and evidence. Human commits use the actual Git identity without AI coauthor trailers; that does not remove academic disclosure. Earlier technical-activity restrictions are not the final project's AI policy.

| Work | Human decisions and AI-assisted changes | Actual verification / limit |
| --- | --- | --- |
| Source selection/design foundation | Selected Doodle, localhost, one app plus worker, four streams and assignment-only assessment; AI assisted source/document inspection, architecture, ERD, component/repository/status contracts and planning | Source inspection found no scanning integration. Read-only design review led to the start-in-flight marker and polling deadline/quota rules. Design is not proof of implementation. |
| Local baseline/manual flashcards | AI assisted installer/renderer/route repairs, role checks, flashcard access/input/review and offline seeder. Coordinator approved manual flashcards/three roles. Review corrected lost Home messages and invalid-form input loss. | [Executed baseline evidence](../../evidence/baseline/routes-flashcards.json), source PR #1 and completion PR #2. Provider/private-download/final integrated evidence remains pending. |
| Documentation consolidation | Coordinator requested one source per topic, removal of obsolete/duplicated guides and saved chat prompt, member-number labels, separate original Steps A-F and fewer repeated feature reminders. AI assisted reorganization/link/traceability checks and read-only review. | Review corrected deleted-path references and restored the deferred service-worker security check. 73 local links and requirement/case IDs verified; technical contract blocks unchanged. Documentation-only; feature cleanup remains postponed. Merge/delivery belong in the team plan. |
| Fork collaboration rules | Coordinator requested durable instructions so members' agents synchronize forks and task branches without repeated prompts. AI assisted the remote/sync/conflict policy and links from existing guides. | Documentation only; task record holds actual review/checks and PR references. No member accounts, forks or credentials were changed by this task. |

For later work record member number, tool/model if known, prompt/work summary, affected artifacts, accepted suggestions, rejected/corrected suggestions with reasons, human review, exact checks and sanitized transcript/evidence locations. Do not invent model versions, effort hours, historical predictions or results.

Review corrections retained: use selected Doodle rather than Moodle assumptions; separate job state/advisory/grade; missing/stale/malformed evidence never means safe; a hash cache lookup is not a fresh scan; an unused MIME helper or upstream marketing is not execution proof; fixture output is not live-provider evidence. The proposed ML-project phases do not add a model-training task.

Original Technical 3 PHP/AI artifacts, preserved transcript and populated historical RMMM were not supplied. Reconcile them through Step B; this register/log does not fabricate or replace missing originals.

## Assessment traceability

| ID | Observable acceptance criterion | Final part | Design and required proof |
| --- | --- | --- | --- |
| R12 | All risks receive an evidence-backed final status; materialized risks compare prediction with outcome | F | `docs/final-project/step-f-risk-closure-and-ai-use-log.md`; do not invent earlier predictions |
| R13 | Actual AI assistance and human review/corrections are disclosed | F | `docs/final-project/step-f-risk-closure-and-ai-use-log.md`; transcript references |
