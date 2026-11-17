# Trends and case-based lessons

Planning evaluation. Update adopted/rejected status and add implementation/evidence links at release; the integration is not built yet.

Evaluate the approved assignment workflow, retained supporting LMS features and new scan integration, not every feature advertised by upstream Doodle. Excluded quizzes/AI/social modules do not become integration achievements. API-first design will be evidenced through the shared component/repository/JSON contracts and connected tests, not the existence of this prose alone.

## Required trends

| Trend | Decision for this scope | Engineering reason and release proof |
| --- | --- | --- |
| Cloud deployment | Rejected for the assessed build | Local demo is agreed; avoids added hosting, persistence and worker-operation work. Trade-off: panel must run locally. Prove clean local setup. |
| Microservice decomposition | Rejected | One codebase/database plus CLI worker meets asynchronous isolation needs without another internal API/auth/deployment boundary. Trade-off: coordinated releases and shared DB. |
| Containerization | Deferred / not required | Native PHP + SQLite is the chosen local setup. Containers add packaging work before baseline functionality is proven. If setup drift prevents reproduction, revisit with measured evidence. Do not claim Docker merely because it is installed. |
| Event-driven integration | Partially adopted in target | A committed submission/rescan produces durable database work processed asynchronously. This is database-backed work dispatch, not a broker-based event platform or provider callback. Show crash/restart and duplicate-delivery tests. |
| API-first design | Adopted in target | Publish the normalized result/status contracts and failure semantics before parallel implementation; fixture compatibility checks exercise them. It does not mean every PHP page needs a REST rewrite. |

GitHub hosts project source and documentation. [GitHub Pages does not run PHP](https://docs.github.com/en/pages/getting-started-with-github-pages/creating-a-github-pages-site); hosting a static page there would not deploy the LMS. This fact explains the choice, not a new cloud task.

## Case 1: Mars Climate Orbiter ground-system integration

NASA's investigation identified inconsistent units across a ground-software interface: data supplied in English units was expected in metric units. A written interface requirement alone did not prevent incompatible semantics reaching the consuming system. Source: [NASA Mars Climate Orbiter Phase I investigation](https://discovery.larc.nasa.gov/PDF_FILES/MCO_report_2.pdf), root-cause section.

**Effect on this design:** publish exact meanings and units for SHA-256 identity, file revision versus submission identity, retry seconds, byte limits and UTC timestamps; validate file-analysis counters and bind evidence to the expected file. See `data-model.md`, integration classification policy and D03/D05. Planned proof: CT02, IT02 and a retry-delay unit check. At release link the actual validation/persistence code and test output; until those exist this is a documented design effect, not an implemented lesson.

## Case 2: GitHub multi-datacenter database/service integration

GitHub's October 2018 incident report describes a network partition followed by database-topology changes, inconsistent service data and interrupted downstream processing. Recovery required protecting data integrity and managing backlogs across dependent systems. Source: [GitHub's October 21 post-incident analysis](https://github.blog/news-insights/company-news/oct21-post-incident-analysis/).

**Effect on this design:** durable submission/attempt state, explicit current pointers, expiring worker leases, preserved analysis IDs and measured restart/backlog recovery take priority over assuming transient failures disappear. See D02, `architecture.md` failure rules and the data-model concurrency invariants. Planned proof: IT03/IT04, FT01/FT03 and recovery measurements. These are proportionate local controls, not a proposal to recreate GitHub's infrastructure.

For final Part E, retain both original sources and add a trace from each lesson to the actual change, test and observed result. Verify that these cases satisfy the instructor's interpretation of documented integration projects. Do not substitute unrelated literature references with no traceable effect.
