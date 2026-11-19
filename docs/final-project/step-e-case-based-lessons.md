# Step E — Case-Based Lessons

## Overview

Each lesson below maps to an implemented control and an executed check.

## Case 1: Mars Climate Orbiter ground-system integration

NASA's investigation identified inconsistent units across a ground-software interface: data supplied in English units was expected in metric units. A written interface requirement alone did not prevent incompatible semantics reaching the consuming system. Source: [NASA Mars Climate Orbiter Phase I investigation](https://discovery.larc.nasa.gov/PDF_FILES/MCO_report_2.pdf), root-cause section.

**Effect on this design:** publish exact meanings and units for SHA-256 identity, file revision versus submission identity, retry seconds, byte limits and UTC timestamps; validate file-analysis counters and bind evidence to the expected file. See [data model](../data-model.md), [integration classification policy](../integrations.md) and [D03/D05](../architecture.md). Implemented in [VirusTotalAdapter](../../app/scan/VirusTotalAdapter.php), [ScanAttemptRepository](../../app/scan/ScanAttemptRepository.php) and [ScanResultProcessor](../../app/scan/ScanResultProcessor.php). Executed adapter, persistence and worker checks cover wrong identities, integer counters, revision isolation and retry seconds.

## Case 2: GitHub multi-datacenter database/service integration

GitHub's October 2018 incident report describes a network partition followed by database-topology changes, inconsistent service data and interrupted downstream processing. Recovery required protecting data integrity and managing backlogs across dependent systems. Source: [GitHub's October 21 post-incident analysis](https://github.blog/news-insights/company-news/oct21-post-incident-analysis/).

**Effect on this design:** durable submission/attempt state, explicit current pointers, expiring worker leases, preserved analysis IDs and measured restart/backlog recovery take priority over assuming transient failures disappear. See [D02 and architecture failure rules](../architecture.md) and the [data-model concurrency invariants](../data-model.md). Implemented in [ScanWorker](../../app/scan/ScanWorker.php) and [ScanProviderBudget](../../app/scan/ScanProviderBudget.php). [Worker checks](../../tests/ScanWorkerTest.php) exercise restart/uncertain starts; [reliability trials](../../evidence/reliability/summary.json) record deterministic recovery and operator-triggered replacement of malformed evidence. These are proportionate local controls, not a proposal to recreate GitHub's infrastructure.

These controls apply the lessons at the scale of this local system. They do not reproduce the operational complexity of either source project.


## Verification mapping

| ID | Observable acceptance criterion | Final part | Design and required proof |
| --- | --- | --- | --- |
| R11 | Two documented cases each cause a traceable design change | E | [these case lessons](#overview); decision, code and test links at release |
