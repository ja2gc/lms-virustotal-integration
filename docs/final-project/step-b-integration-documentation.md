# Step B — Integration Documentation

## Overview

Use the canonical artifacts rather than copying contracts here:

| Required artifact | Project artifact / remaining work |
| --- | --- |
| Corrected integration/context/sequence models | [Architecture](../architecture.md), [ERD/state model](../data-model.md); reconcile original Technical 1 |
| Integration register and failure/version semantics | [Integration contracts](../integrations.md); verify each point against built code |
| Reusable Technical 3 component/interface and two consumers | [Component contract](../integrations.md#published-reusable-component); P2 published via PR #7; both real consumers pass connected checks; synthetic provider exchange and completed evidence verified; original source comparison unavailable |
| Release/deployment plan | [Step A delivery](step-a-working-integrated-system.md); localhost selected, no cloud deployment |
| Divergences from technical activities | [Architecture decisions/divergences](../architecture.md#decisions-and-divergences); add reasons and source/evidence as built |

## Source reconciliation

The supplied sources are Technical 2, Technical 3, Module 7 lecture slides and the pasted final Parts A-F. Technical 3 supersedes earlier result-classification and component-boundary proposals where it revises them. Its prototype execution was explicitly unverified. Technical 2's prose test claims are historical claims, not current raw evidence.

Not supplied: Technical 1 corrected models; Technical 4 artifact; the group's original populated RMMM register; original reusable PHP files and preserved AI transcript; full second-submission/live-session instructions. Reconcile missing sources before final submission. This foundation neither fabricates those artifacts nor claims their requirements are satisfied.

## Verification mapping

| ID | Observable acceptance criterion | Final part | Design and required proof |
| --- | --- | --- | --- |
| R04 | Automatic and teacher-requested processing use the same unchanged result interface | B | Two real call sites, compatibility tests, published contract |
| R07 | Every boundary lists protocol, format, authentication, synchronicity, errors, retry and versioning | B | `integrations.md`; reconcile with built code |
| R14 | Corrected Technical 1 models and Technical 4 release work are reconciled with the system as built | B | Source artifacts still required; divergence log |
