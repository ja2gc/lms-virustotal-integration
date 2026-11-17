# Development and UI conventions

## Existing patterns to reuse

Doodle is PHP with server-rendered views, PDO helpers and vanilla CSS/JavaScript. It is not React: no React hooks, component library or frontend build pipeline is needed for this feature.

| Responsibility | Existing location | Planned extension |
| --- | --- | --- |
| Request routing / CSRF | `app/core/Router.php`, `app/core/CSRF.php` | Correct baseline dispatch/method issues; keep same-origin session routes |
| Role and course ownership | `app/core/Auth.php` | Reuse role checks and add explicit submission/file scope checks |
| Submission handling | `app/controllers/StudentController.php` | Delegate file revision and durable scan scheduling to `app/scan/` host services |
| Teacher review | `app/controllers/TeacherController.php`, `app/views/teacher/assignment.php`, `app/views/teacher/grade.php` | Authorized scan details, history, rescan request; repair grade routing |
| Student status | `app/views/assignment/view.php`, `app/views/student/submit_assignment.php` | Current revision's scan state and advisory separate from grade |
| Shared presentation | `app/views/layout/`, `public/css/style.css`, `app/helpers/functions.php` | One reusable scan-status partial and one reason/label formatter |
| Data access | `config/database.php` | PDO transactions and prepared statements; migrations and scan repositories follow existing style |
| Provider integration | None found | Small `app/scan/` area; one bootstrap explicitly loaded by web and CLI |

Proposed shared view: `app/views/scan/_status.php`; JavaScript polling: `public/js/scan-status.js`. Keep these plain and scoped. Avoid an app-wide event/state framework. Use text labels with existing badges; never rely on color alone. Escape original file names and normalized explanatory text.

## Screen behavior

Read [approved features](feature-scope.md): retain courses/materials/enrollment, assignments/deadlines, grading/feedback, dashboards, announcements, in-app notifications, existing calendar and basic profile/theme behavior. Exclude quiz creation/taking/auto-grading and the listed unrelated modules. No source removal has occurred in this planning session.

The UI stream owns all student/teacher templates, status/history partials, navigation and CSS/JS. HTTP/access owns status/rescan/download controllers and the action map. Foundation/data owns submission/revision/attempt SQL and the Student controller's submission seam; scanning owns adapter/worker logic. Follow [exact boundaries](implementation-plan.md#workstream-boundaries-and-safe-handoffs), not separate competing student/teacher implementations.

- Submission confirmation means saved, with a separate scan-pending display. No attachment means Not applicable. Preserve fields when validation fails.
- Show queued, checking, waiting/retrying, completed or unresolved processing information separately from the four advisory labels. A completed check can be inconclusive.
- Student sees only their submission's current file revision and permitted result summary. Teacher/admin sees authorized review details, counts, checked time, prior attempts and a rescan action. Never show a previous file's result as the replacement's result.
- Poll status every 15 seconds while active, pause when the page is hidden, stop after a terminal state, and back off after transient errors. Browser polling never triggers provider calls. A failed refresh keeps the last known display with a refresh error; it does not reset the advisory.
- Rescan is a CSRF-protected POST with a server-issued idempotency token; disable repeated clicking while submitting but enforce deduplication on the server too. State clearly when a new check is pending.
- Downloads are authenticated attachments, not inline previews or public storage URLs. Show advisory warnings near the action without implying a guarantee of safety.
- Verify keyboard access, persistent labels, narrow screens, long names, empty/error states and text enlargement. Keep the upstream visual language and familiar navigation.

## Team workflow

1. Everyone reads `AGENTS.md`, scope, baseline audit and their task's canonical contracts.
2. Merge baseline repairs and the common migration/interface contract before parallel features depend on them.
3. Create short task branches (for example `fix/baseline-workflow`, `feat/scan-adapter`, `feat/scan-worker`, `feat/student-scan-status`, `feat/teacher-rescan`). Commit complete behavior with focused tests and affected documentation.
4. A PR states the observable change, test command/results, migration/config impact and remaining limitation. Another member reviews shared contracts and affected callers before merge. Never claim another member's work through a rewritten author identity.
5. Sync with main frequently. Avoid simultaneous edits to the same controller/helper. When two tasks need one file, merge its common seam first rather than maintaining divergent versions.
6. Each implementer supplies their own tests and evidence. One member may coordinate validation/release; quality is shared.
7. Keep numeric evidence, risk updates and actual AI-use notes with the relevant change. At release reconcile planned versus built behavior rather than retaining outdated promises.
8. Handoff a merged revision with signatures/JSON examples, migration/config needs, real test output and limitations. Producers update contracts before consumers switch. Request a missing repository/route capability from its owner rather than editing another stream's source concurrently.
9. Merge P0a's rendering/installer sweep first; P0b's local serving/action boundary and P1's shared schema/interface can then proceed in parallel on their owned files. Both must be ready before dependent endpoints/whole-flow acceptance. Use fixtures during isolated work and integrate a complete vertical slice before UI polish. All members supply their own tests/evidence; no commit-count or line-count target applies.

## Agent task prompt template

> Read AGENTS.md and the linked context for task [ID] in docs/implementation-plan.md. Inspect current source and Git status. Implement only that task using the published interfaces. Reproduce failures and add focused checks before changing behavior. Reuse Doodle patterns; do not introduce a new framework or provider. Update affected canonical docs. Report files changed, exact checks and outcomes, known limitations, and anything not implemented. Do not commit credentials, rewrite history, push, or implement other members' tasks unless explicitly authorized.

Keep task-to-person assignments in team messages, not this guide. Use the actual member's configured Git name/email for their commits; do not add AI co-author trailers. Preserve required academic disclosure in `ai-use.md`.
