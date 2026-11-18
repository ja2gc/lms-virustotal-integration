# Task ownership and completion record

This is the shared execution record. [Implementation plan](implementation-plan.md) owns task details; [feature scope](feature-scope.md) owns cleanup decisions. Claim a stream in the group chat before changing its files, then enter your name here through your branch/PR. Open slots are not permission to work on another member's shared files.

| Stream | Owner | Current state | Next work / dependency |
| --- | --- | --- | --- |
| 1 — foundation/data | Unclaimed | Ready to plan P1 | Baseline renderer/runner already exists. Publish migrations, immutable file/submission/attempt repository and shared interfaces. Coordinate existing shared-file edits with Jared's cleanup. |
| 2 — scanning engine | Unclaimed | Isolated planning/fixtures can start | P2 adapter and P3 worker; connected persistence waits for P1. No duplicate component or status classifier. |
| 3 — LMS interface | Unclaimed | Isolated scan partials/fixtures can start | P4 status/history/polling UI; keep manual flashcards. Wait for Jared's cleanup merge before editing header/navigation/dashboard files; real endpoints/results require P3/P5. |
| 4 — HTTP/access/local delivery | **Jared** | **Reserved — next chat waits for “continue”** | First P0b cleanup/action map plus one-time menu/dashboard sweep. Then P5 status/rescan/private downloads after P1's contract is merged. Use [the exact handoff](handoffs/jared-member-4.md). |

P6/P7 evidence and rehearsal are shared. Choosing a stream means owning its implementation, checks, documentation and PR handoff, not just its code.

## Completed and pending foundation work

- [x] Documentation foundation, SRS, contracts/diagrams, team guide and full manual acceptance plan published.
- [x] Local installer/renderer/course/grade routes and sidebar selection repaired; localhost runbook and evidence published in `645a43a`.
- [ ] Current route/role/flashcard/demo-seeder follow-up reviewed, merged and verified on main. Owner: Jared. See the actual evidence in `evidence/baseline/routes-flashcards.json` when generated; do not infer a pass from this line.
- [ ] **P0b feature cleanup/action restrictions/menu sweep — Jared, NOT STARTED. Explicitly deferred to next chat.**
- [ ] P1 submission/private-revision/attempt persistence and interfaces.
- [ ] P2/P3 provider and worker, including both reusable-component consumers.
- [ ] P4 connected scan UI and assignment-only presentation acceptance.
- [ ] P5 authorized status/rescan/private downloads (Jared, depends on P1 and cleanup).
- [ ] P6/P7 full manual acceptance, real integration, load/fault/security/restore evidence, final documentation/rehearsal.

## Required member handoff entry

For each task/PR add an entry below: task ID, owner, branch/PR, state (In progress / Ready for review / Done / Waiting for dependency), changed behavior/files, exact checks and results, evidence paths, contract/config/migration effects, downstream consumers and unresolved work. Link the merge reference when Done. Keep secrets and local DBs out of the record. Update task-plan checkboxes truthfully; leave shared integration gates open until the combined system passes.

### Current follow-up — Jared

State: Ready for review, before merge/main verification. Scope: all current sidebar routes and literal link/redirect resolution, three-role access checks, manual flashcard repair and localhost demo seeding. Evidence: `evidence/baseline/routes-flashcards.json`. Review found a lost Home flash message and discarded invalid-form input; both have focused regressions and corrections. No excluded module has been removed and no VirusTotal feature has been implemented. The final delivery reference will close this entry.
