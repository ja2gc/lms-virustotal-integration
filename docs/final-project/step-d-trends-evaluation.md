# Step D — Trends Evaluation

## Original submission instruction — Part D

> Evaluate your system against current directions in systems integration: cloud deployment, micro-service decomposition, containerization, event-driven integration, and API-first design. For each, state whether your system adopts it, and justify both the adoptions and the rejections on engineering grounds rather than on familiarity.

## Project response

Planning decisions must be reconciled with built implementation/evidence at release.

| Trend | Decision for this scope | Engineering reason and release proof |
| --- | --- | --- |
| Cloud deployment | Rejected for the assessed build | Local demo is agreed; avoids added hosting, persistence and worker-operation work. Trade-off: panel must run locally. Prove clean local setup. |
| Microservice decomposition | Rejected | One codebase/database plus CLI worker meets asynchronous isolation needs without another internal API/auth/deployment boundary. Trade-off: coordinated releases and shared DB. |
| Containerization | Deferred / not required | Native PHP + SQLite is the chosen local setup. Containers add packaging work before baseline functionality is proven. If setup drift prevents reproduction, revisit with measured evidence. Do not claim Docker merely because it is installed. |
| Event-driven integration | Partially adopted in target | A committed submission/rescan produces durable database work processed asynchronously. This is database-backed work dispatch, not a broker-based event platform or provider callback. Show crash/restart and duplicate-delivery tests. |
| API-first design | Adopted in target | Publish the normalized result/status contracts and failure semantics before parallel implementation; fixture compatibility checks exercise them. It does not mean every PHP page needs a REST rewrite. |

GitHub hosts project source and documentation. [GitHub Pages does not run PHP](https://docs.github.com/en/pages/getting-started-with-github-pages/creating-a-github-pages-site); hosting a static page there would not deploy the LMS. This fact explains the choice, not a new cloud task.

## Assessment traceability

| ID | Observable acceptance criterion | Final part | Design and required proof |
| --- | --- | --- | --- |
| R10 | All five required trends have an adoption/rejection decision and engineering reason | D | `docs/final-project/step-d-trends-evaluation.md`; final implementation references |
