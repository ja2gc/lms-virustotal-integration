# Agent entry point

LMSTotal extends Doodle with private file submission and VirusTotal integration. Read [README](README.md#project-documents), [workflow](.agents/rules/workflow.md), [SRS](docs/SRS.md), [integration contracts](docs/integrations.md) and the relevant [team record](docs/team-plan.md) sections before work.

- The SRS owns behavior and the [supported action matrix](docs/SRS.md#cleanup-action-matrix). Source, executed checks and sanitized evidence establish implementation truth.
- Member 4 maintains integrated implementation. Current member responsibilities and remaining acceptance gates are in the team record. Do not claim unexecuted manual or independent acceptance from fixtures.
- Before work/publication, follow [synchronization](.agents/rules/workflow.md#fork-synchronization): verify remotes, fetch team main and preserve local changes. Members submit code PRs from their own forks; coordinator publication follows explicit authorization.
- Preserve existing databases, uploads, private bytes, schema history and upstream license/notices. Never tool-read real environment files, print credentials or commit private data.
- Keep status/advisory/grade separate. Missing, stale or malformed evidence remains conservative. Provider work belongs in the metered worker, never status GETs or submission HTTP requests.
- Use the existing PHP/PDO/views/JavaScript structure and applicable skills. Reproduce behavioral defects, add focused failing checks, fix the cause and verify affected workflows.
- Update canonical contracts when interfaces change. Keep documentation concise and current, with truthful verification/disclosure and no fabricated provenance.

| Work | Additional context |
| --- | --- |
| Persistence/worker | docs/data-model.md, docs/integrations.md, docs/engineering.md |
| UI/HTTP | docs/engineering.md, docs/demo-manual.md |
| Setup/recovery | README.md, docs/final-project/step-a-working-integrated-system.md |
| Verification | docs/final-project/step-c-performance-reliability-security-validation.md |
| Project guides | A-F files indexed in README |
