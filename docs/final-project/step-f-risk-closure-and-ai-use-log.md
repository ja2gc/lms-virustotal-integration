# Step F - Risks and review

## Current risk register

Reviewed by Member 4 on 2026-11-19. Statuses describe observed controls and residual exposure; historical likelihood scores/predictions are not invented.

| ID | Risk | Control and observed outcome | Status |
| --- | --- | --- | --- |
| RK01 | Core workflow/runtime faults | Actual installation and role/teaching/learning regressions identify and verify corrections, including SQLite enrollment/progress. | materialized |
| RK02 | Provider quotas and latency | Persisted shared reservations, bounded polls and conservative operator pause. External usage/availability remain outside the app. | mitigated |
| RK03 | Timeout, failed request or malformed evidence | Nine deterministic trials preserve saved work and recover by scheduled reads or explicit fresh request. | mitigated |
| RK04 | File/indicator disclosure or unauthorized access | Synthetic-only live policy, private immutable storage, server authorization and blocked static paths; audience filters apply to notices. | mitigated |
| RK05 | Stale/duplicate results corrupt current status | Revision/attempt IDs, leases, pointers and idempotent request tokens pass persistence/worker/connected checks. | mitigated |
| RK06 | Contract drift | Shared interfaces, both real consumers, fixture checks and scoped source review. Live QA identified and corrected a strict provider-ID alias case. | mitigated |
| RK07 | Original source/provenance unavailable | T1/T4/original register/component/transcript gaps remain explicit; no historical artifacts fabricated. | still open |
| RK08 | Conservative classification is often inconclusive | Counts and reasons remain visible. The real synthetic sample completed Unresolved because engines reported unsupported types. | mitigated |
| RK09 | Setup/network/recording failure | Fresh installer/setup/worker tests and a saved Playwright rehearsal pass. Independent machine/team rehearsal remains outstanding. | still open |
| RK10 | Scope/maintenance drift | Public actions are explicitly allowlisted; one PHP app, SQLite and worker remain the supported design. | mitigated |

## Verification references

[Integrated summary](../../evidence/validation/summary.json), [performance](../../evidence/performance/summary.json), [reliability](../../evidence/reliability/summary.json), [real synthetic exchange](../../evidence/live/synthetic-check.json) and [manual acceptance status](../../evidence/uat/acceptance-status.json). The engineering guide owns boundary controls; the team record owns task/acceptance status.

Matched database/private-file restore is verified in a disposable target. Independent setup, full human acceptance and absent original source reconciliation remain open. Provider availability cannot be closed by fixture tests. A valid Unresolved advisory is not relabeled as a favorable result for presentation.

## AI-use log

Human contributors own scope, architecture decisions, review and release. AI assistance supported source analysis, implementation, tests and documentation. Upstream notices/history and the required disclosure remain; human commits use the configured identity without AI coauthor trailers.

| Work | Assistance and review | Observed correction or limit |
| --- | --- | --- |
| Design/contracts | Inspected upstream behavior and published revision/attempt/status/failure semantics | Separate academic grade, processing state and advisory; explicit uncertain-start marker and fixed polling deadline |
| Baseline/shared persistence | Rendering/install/route repairs, migration/repository, role boundaries and regression checks | Deletion/reference compatibility, transactions, lease expiry and current-pointer isolation verified |
| P2 | Member 2's result adapter/transport and offline fixtures, reviewed via PR #7 | Required provider schema/counters, identity agreement and retry hints corrected and verified |
| Integrated implementation | Member 4 directed private storage/import, worker/budget/two consumers and scan UI | Tests preceded behavioral changes; separate read-only reviews covered runtime boundaries |
| Review/QA corrections | Reproduced failures and added focused regressions | DOCX inspection, commit/import rollback, form state, fixture provenance, SQLite runtime, role-targeted announcements and provider descriptor alias |
| Verification/docs | Executed local suites, full load ramp, fault/restore trials, limited synthetic provider exchange and recording rehearsal | Fixtures/live/late follow-up/human acceptance remain distinct; no fabricated elapsed effort or stakeholder sign-off |

Member setup/recording instructions are delivered separately. No real student content, private environment values, API keys or raw provider bodies are published. The application loads its local key server-side for authorized live tests; tools did not inspect the environment file.

## Verification mapping

| ID | Criterion | Evidence |
| --- | --- | --- |
| R12 | Risks have observed outcomes, controls and explicit residual exposure | Current register and linked validation |
| R13 | Actual AI assistance and human review/corrections are disclosed | AI-use log above |
