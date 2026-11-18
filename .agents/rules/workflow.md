# Shared workflow

## Scope and truth

- Follow the current user request. A planning task produces plans; an assigned implementation task produces only its agreed changes.
- Read root `AGENTS.md` first. Read this file explicitly; do not assume agents automatically discover `.agents/`.
- Honor explicit phase gates. A parked next chat performs no tools/work before **continue**. Later steering extends unfinished scope unless the user explicitly replaces it. The current cleanup is deferred, not completed.
- Read `docs/SRS.md` for required behavior before planning. Map assigned work to its FR/NFR/R IDs and the manual UAT/demo cases. Source/evidence establish current behavior; a mismatch with the SRS is an implementation gap or an explicit change request, not an automatic scope revision.
- Source, migrations, executed checks and observed behavior establish implementation truth. Label planned, implemented and verified behavior separately.
- Reuse existing controllers, PHP views, CSS, authorization and database helpers where they fit. Inspect implementations and callers before adding a helper, service or component.
- Follow `docs/feature-scope.md`: assignment-based assessment only, with its retained supporting LMS features. Do not rebuild quizzes or silently remove approved calendar/announcements/notifications/course behavior. Propose further scope changes before edits.
- Use Superpowers for design/planning, debugging and verification when available; use Ponytail to keep implementation minimal. Skill availability is agent-specific: these repository rules still apply without those plugins.
- Use the applicable installed skills before acting: using-superpowers at session start, systematic-debugging for defects, test-driven-development for behavior changes, writing-plans/executing-plans for multi-step approved work, verification-before-completion before claims and requesting/receiving-code-review for review. Route coding/simplification through the installed Ponytail entry workflow. Use concise communication; use Caveman when installed/requested without losing clarity. Discover current skill locations; never copy another machine's paths. No Laravel/React/Expo/clinical rules apply to this plain-PHP LMS.
- Ask one material design question at a time and include a recommendation. Resolve routine choices within the agreed scope.

## Code and collaboration

- Inspect Git status first; preserve other members' changes. Work in a task branch and submit a focused PR. Never reset, overwrite history, force-push or stage unrelated files.
- Members branch from current main and submit PRs; Jared reviews/contracts/overlaps and integrates them. Do not push directly to main or merge another member's work without his explicit instruction. Rebase safely after shared baseline changes, then rerun affected checks; a conflict-free merge alone is not semantic verification.
- No new frontend framework, microservice, message broker, AI model or provider unless a concrete requirement warrants it and the shared design is updated.
- Reproduce bugs before fixing them. Add a focused failing check for behavioral fixes and integration logic, then implement and verify it. Documentation-only changes need links and consistency checks.
- Update canonical contracts before a shared interface changes. Include affected consumers in the PR. Do not silently change status meanings or add duplicate classifiers.
- Respect one primary task owner per shared file. Merge the baseline rendering sweep before feature branches modify controllers; freeze common persistence/test contracts before dependent work. Templates/CSS/JS, persistence, provider/worker, and HTTP/access each have a separate workstream. Request changes from the owning stream instead of editing its files concurrently.
- Use forward migrations for project changes; do not edit an already applied migration. Keep a record of deviations from the technical documents.
- Do not create subagents or worktrees unless the user requests them or an explicitly invoked workflow requires a scoped review. Keep reviewers read-only.
- Keep removals reversible and narrow. Trace shared callers and compare the exact feature-scope matrix before removal; preserve migrations/schema history and unrelated local data. After three failed fixes, reassess the root cause instead of stacking patches.

## Security and evidence

- Never read non-example `.env` files, print credentials, or commit tokens, local databases, sessions, real student work or private backups.
- Keep VirusTotal credentials server-side. Use synthetic files and fictional users. A file hash lookup is also an external disclosure; do not send confidential files or indicators.
- Enforce authorization on the server, including file downloads, result reads and rescan requests. A hidden button is not access control.
- An upload accepted by the LMS is independent of its security advisory. Failed, missing, stale or malformed evidence must never become a favorable advisory.
- Logs identify operations and failures without file bytes, provider keys, cookies or unsanitized provider bodies.
- Report exactly what was tested. Fixtures do not prove live VirusTotal behavior; syntax checks do not prove a working page; a backup does not prove restore.
- Seed only fictional input records with stable identity keys. Normal demo seeding is offline, repeatable and non-destructive; run twice and check duplicates/relationships. Do not fabricate scan results, grade history, notifications or completed reviews. Public localhost demo passwords may be documented only when explicitly approved; they are not production/provider credentials. Never reset a changed account silently.
- Shared navigation, notifications, announcements and permissions require checks for all relevant roles. Test retained workflows and denied paths after cleanup. Use the native in-app preview for visible localhost checks when available; do not substitute an HTTP status for rendered success.

## Documentation and Git identity

- Maintain one authoritative document per topic; link rather than duplicate. Update affected guides when implementation changes the contract or workflow.
- Read/update `docs/task-status.md`: claim an owner, record actual changes, exact checks/evidence, PR and dependencies. Use Ready for review before merge; tick Done only after review/merge/verification. Update corresponding plan checkboxes and AI disclosure at handoff. Keep execution history in that record/evidence rather than scattering it across every guide. The canonical demo/UAT guide is `docs/demo-manual.md`; do not copy Nutriscope's storyboard paths.
- Retain Doodle's original license, notices and history. New commits use the real contributor's configured Git identity. Do not add AI/Codex co-author trailers or bot credit to human commits.
- Keep the required academic AI disclosure and actual review/correction record in `docs/ai-use.md`; the commit-credit rule does not remove disclosure obligations.
- No production deployment is in scope. Local demonstration is the agreed target. Never reset a member's local database or publish externally without task authorization.
