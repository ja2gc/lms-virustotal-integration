# Step B — Integration Documentation

## Overview

Use the canonical artifacts rather than copying contracts here:

| Required artifact | Project artifact |
| --- | --- |
| Corrected integration/context/sequence models | [Architecture](../architecture.md), [ERD/state model](../data-model.md) |
| Integration register and failure/version semantics | [Integration contracts](../integrations.md); boundary and consumer contracts |
| Reusable Technical 3 component/interface and two consumers | [Component contract](../integrations.md#published-reusable-component); P2 published via PR #7; both real consumers pass connected checks; synthetic provider exchange and completed evidence verified |
| Release/deployment plan | [Step A delivery](step-a-working-integrated-system.md); localhost selected, no cloud deployment |
| Divergences from technical activities | [Architecture decisions/divergences](../architecture.md#decisions-and-divergences); decisions and implementation references |

## Design basis

The supplied sources are Technical 2, Technical 3, Module 7 lecture slides and the pasted final Parts A-F. Technical 3 supersedes earlier result-classification and component-boundary proposals where it revises them. Its prototype execution was explicitly unverified. Technical 2's prose test claims are historical claims, not current raw evidence.

## Verification mapping

| ID | Observable acceptance criterion | Final part | Design and required proof |
| --- | --- | --- | --- |
| R04 | Automatic and teacher-requested processing use the same unchanged result interface | B | Two real call sites, compatibility tests, published contract |
| R07 | Every boundary lists protocol, format, authentication, synchronicity, errors, retry and versioning | B | `integrations.md`; implemented consumer notes |
| R14 | Corrected Technical 1 models and Technical 4 release work are reconciled with the system as built | B | Historical source comparison is outside recorded verification scope; see the architecture divergence log |
