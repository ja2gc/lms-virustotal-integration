# Agent entry point

This repository extends Doodle for **Secure File Submission System with VirusTotal Integration**.

Before work, read [the project index](docs/README.md), [workflow rules](.agents/rules/workflow.md), and the [Software Requirements Specification](docs/SRS.md). The SRS defines required behavior; source/evidence establish what is actually implemented. A difference is a gap to resolve, not permission to silently redefine the requirement. Then read the guides relevant to the task:

Read [approved features and workflow](docs/feature-scope.md) before changing any page or route. Assessments are assignments only: retain assignment grading/feedback; exclude quiz creation, taking and auto-grading. Further removals require a proposal before edits. Follow the file and handoff boundaries in `docs/implementation-plan.md`; do not optimize assignments for anyone's commit count.

Manual flashcards are now approved; preserve their create/view/study/review/delete workflow and object permissions. Generated decks remain excluded. The exact cleanup matrix in `docs/feature-scope.md` owns what to retain/deny; do not follow an old flashcard exclusion. Cleanup is reserved for Jared's next session and must not start before his explicit **continue**.

Read [task status](docs/task-status.md) before choosing work. Work on a task branch created from current `main`; commit only your task files and open a focused PR. Members must not push/merge their work directly to `main`; Jared reviews and integrates PRs, checks producer/consumer contracts and overlapping files, and reruns affected checks on the merged result. A PR or green isolated test is not integration proof.

When finishing or handing off, update the task record and corresponding plan checkboxes with owner, actual changes, tests/evidence, remaining blockers and PR/merge reference. Use Ready for review until reviewed/merged/verified; mark Done only then. Never tick unfinished cleanup/integration tasks. Record actual AI assistance in `docs/ai-use.md` and preserve the no-AI-coauthor Git rule.

| Task | Required context |
| --- | --- |
| Architecture or shared behavior | `docs/architecture.md`, `docs/data-model.md` |
| API, worker, reusable component | `docs/integrations.md`, `docs/data-model.md`, `docs/security.md` |
| Screens, forms, shared helpers | `docs/development.md`, `docs/security.md` |
| Implementation task | `docs/team-guide.md`, `docs/implementation-plan.md`, `docs/demo-manual.md`, plus the task's referenced guides |
| Tests or evidence | `docs/validation.md`, `docs/baseline-audit.md` |
| Setup, demonstration or recovery | `docs/release.md`, `docs/demo-manual.md` |
| Final submission | `docs/SRS.md`, `docs/evaluation.md`, `docs/risks.md`, `docs/ai-use.md` |

The new integration is a **design baseline, not implemented software**. Check source and evidence before claiming otherwise. Existing upstream README claims are not verification. Do not start implementing the whole plan when assigned one task.
