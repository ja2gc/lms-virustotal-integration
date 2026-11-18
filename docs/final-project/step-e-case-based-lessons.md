# Step E — Case-Based Lessons

## Original submission instruction — Part E

> Investigate at least TWO (2) documented real-world integration projects. For each, identify what went wrong or what succeeded, and state the specific change it produced in your own design. A lesson with no traceable effect on your system is not a lesson.

## Project response

These are documented design effects. Add actual code/test references when implemented; a source summary alone does not satisfy the requirement.

## Case 1: Mars Climate Orbiter ground-system integration

NASA's investigation identified inconsistent units across a ground-software interface: data supplied in English units was expected in metric units. A written interface requirement alone did not prevent incompatible semantics reaching the consuming system. Source: [NASA Mars Climate Orbiter Phase I investigation](https://discovery.larc.nasa.gov/PDF_FILES/MCO_report_2.pdf), root-cause section.

**Effect on this design:** publish exact meanings and units for SHA-256 identity, file revision versus submission identity, retry seconds, byte limits and UTC timestamps; validate file-analysis counters and bind evidence to the expected file. See [data model](../data-model.md), [integration classification policy](../integrations.md) and [D03/D05](../architecture.md). Planned proof: CT02, IT02 and a retry-delay unit check. At release link the actual validation/persistence code and test output; until those exist this is a documented design effect, not an implemented lesson.

## Case 2: GitHub multi-datacenter database/service integration

GitHub's October 2018 incident report describes a network partition followed by database-topology changes, inconsistent service data and interrupted downstream processing. Recovery required protecting data integrity and managing backlogs across dependent systems. Source: [GitHub's October 21 post-incident analysis](https://github.blog/news-insights/company-news/oct21-post-incident-analysis/).

**Effect on this design:** durable submission/attempt state, explicit current pointers, expiring worker leases, preserved analysis IDs and measured restart/backlog recovery take priority over assuming transient failures disappear. See [D02 and architecture failure rules](../architecture.md) and the [data-model concurrency invariants](../data-model.md). Planned proof: IT03/IT04, FT01/FT03 and recovery measurements. These are proportionate local controls, not a proposal to recreate GitHub's infrastructure.

For final Part E, retain both original sources and add a trace from each lesson to the actual change, test and observed result. Verify that these cases satisfy the instructor's interpretation of documented integration projects. Do not substitute unrelated literature references with no traceable effect.


## Assessment traceability

| ID | Observable acceptance criterion | Final part | Design and required proof |
| --- | --- | --- | --- |
| R11 | Two documented cases each cause a traceable design change | E | [these case lessons](#project-response); decision, code and test links at release |
