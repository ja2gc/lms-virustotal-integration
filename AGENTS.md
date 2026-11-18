# Agent entry point

This repository extends Doodle for **Secure File Submission System with VirusTotal Integration**. Read [README](README.md#project-documents), [.agents/rules/workflow.md](.agents/rules/workflow.md), [SRS](docs/SRS.md) and your assigned member/task sections in [the team plan](docs/team-plan.md) before work.

The SRS owns final behavior and the [cleanup matrix](docs/SRS.md#cleanup-action-matrix); source/tests/evidence establish implementation truth. Apply that matrix without further scope changes unless proposed first. P0b cleanup is complete. Current member ownership and P1a/P1b/P2-P7 statuses are authoritative in docs/team-plan.md. Complete only assigned work; fixture or interface checks must not mark another task Done.

| Task | Additional context |
| --- | --- |
| Design/shared behavior | docs/architecture.md, docs/data-model.md, docs/integrations.md |
| API/worker/persistence | docs/integrations.md, docs/data-model.md, docs/engineering.md |
| UI/forms | docs/engineering.md, docs/demo-manual.md |
| Setup/recovery | README.md, docs/final-project/step-a-working-integrated-system.md |
| Verification | docs/final-project/step-c-performance-reliability-security-validation.md, docs/demo-manual.md |
| Final submission | Step A-F files in docs/final-project/, indexed in README |

Before starting/resuming work and before submitting/updating a PR, follow [fork synchronization](.agents/rules/workflow.md#fork-synchronization): fetch the team main, preserve local work and bring applicable updates into your task branch. Push member changes to their own fork and open a PR to the team main. Follow the other branch/PR, applicable-skill, privacy, test and completion-record rules in that file. Use member numbers in docs. Do not infer implemented scanning from design or execute every stream when assigned one.
