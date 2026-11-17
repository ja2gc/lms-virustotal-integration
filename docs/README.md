# Project foundation

**LMS with VirusTotal Integration** extends [Doodle](https://github.com/deathlegionteamlk/doodle). Technical 3's original title is *Secure File Submission System with VirusTotal Integration*. The agreed direction is one PHP application, a background scan worker from the same codebase, SQLite for the local demonstration, and VirusTotal as the external service.

## Read in this order

1. [Scope and final deliverable mapping](project-scope.md)
2. [Inspected baseline and known defects](baseline-audit.md)
3. [Architecture, diagrams and decisions](architecture.md)
4. [Data model and ERD](data-model.md)
5. [Integration register and reusable contract](integrations.md)
6. [Development, UI conventions and collaboration](development.md)
7. [Security controls](security.md)
8. [Implementation plan](implementation-plan.md)
9. [Validation and evidence](validation.md)
10. [Local release and demonstration](release.md)
11. [Trends and case-based lessons](evaluation.md)
12. [Risk register](risks.md)
13. [AI-use record](ai-use.md)

For the scope-removal discussion, read [Doodle's extra features and recommendations](feature-scope.md). No optional module has been removed or disabled yet.

## Status of this foundation

These guides specify work to build. No VirusTotal feature, database migration, worker, new UI, application repair or end-to-end demonstration has been implemented by this documentation session. [Baseline evidence](../evidence/baseline/php-checks.json) records real checks, including failures.

Agreed by the project coordinator: Doodle baseline; one application plus worker; localhost demonstration. Technical policies below are proposed engineering defaults unless explicitly described as agreed. Validate them in the first implementation PR; record any material change in the [decision log](architecture.md#decisions-and-divergences).

Keep task-to-person assignments in team communication. Repository tasks describe interfaces and acceptance criteria without assigning classmates by name.
