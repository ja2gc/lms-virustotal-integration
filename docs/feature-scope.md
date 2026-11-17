# Doodle features versus the supplied technical scope

Comparison is against the supplied Technical 2 and Technical 3, not missing Technical 1/4 or an unseen requirements document. A controller/schema/template establishes source presence, not a working feature. No optional feature has been removed or disabled during the foundation session.

## Core to retain and extend

Keep accounts/roles, course ownership/enrollment, assignments/deadlines, written/file submissions, teacher review/grading, student feedback, minimum administration and existing in-app feedback/notifications needed by that workflow. Extend these with the new security integration. Course lessons/catalog can remain supporting LMS structure; do not rebuild them.

## Extras not required by the supplied technical documents

| Extra | Source evidence | Recommendation for this course build |
| --- | --- | --- |
| AI study assistant: chat, summaries, generated practice quizzes | `AIController.php`, `AIEngine.php`, `app/views/ai/` | Exclude from assessed scope and navigation; no external LLM integration needed |
| Gamification: points, badges, streaks, leaderboard | `GamificationController.php`, `Gamification.php`, v2 schema | Exclude visible feature; preserve shared calls until deliberately decoupled |
| Spaced-repetition flashcards | `FlashcardController.php`, views and v2 schema | Exclude; remove sidebar due-count queries if hiding the feature |
| Direct messaging | `MessageController.php`, views and sidebar unread counts | Exclude; remove associated header queries as part of a scoped simplification |
| Calendar and calendar export | `CalendarController.php`, views | Exclude separate calendar UI; retain assignment due dates |
| Certificates and public verification | `CertificateController.php`, views | Exclude; do not spend effort repairing this optional controller's runtime path |
| Polls and surveys | `PollController.php`, views | Exclude |
| Learning paths across courses | `LearningPathController.php`, views | Exclude |
| Extended analytics dashboards | `AnalyticsController.php`, views | Exclude feature dashboards; keep the project's operational scan/test measurements |
| Discussion forums | `ForumController.php`, views | Exclude |
| Quiz bank, timed quizzes and auto-grading | `QuizController.php`, TeacherController quiz actions, views | Exclude quizzes; retain assignment grading, which is a different workflow |
| Course reviews/ratings, private notes, bookmarks and global search | `ReviewController.php`, review views | Exclude these destinations; avoid deleting shared course data blindly |
| Collaborative whiteboard | `WhiteboardController.php`, views | Exclude |
| Code exercises/playground | `CodeController.php`, views, v2/v3 schema | Exclude; no code-execution feature is needed for file advisory integration |
| Course wiki and student groups | Classes in `WikiGroupPushBackupController.php`, wiki/group views | Exclude; combined controller filename also requires routing/loading review before these could be called functional |
| Google/GitHub OAuth sign-in | `OAuthController.php` | Leave unconfigured; local session login is sufficient and does not add another assessed external API |
| Generic mobile/consumer REST API and user API tokens | `ApiController.php`, API-token view | Exclude generic token-management UI; retain the new scoped session-protected status/rescan routes |
| Web-push and email delivery | Push class/v3 schema; release notes describe incomplete sending work | Exclude external delivery; these are not proven working integrations and need not be completed |
| PWA/service-worker behavior | `public/sw.js`, manifest, header registration | Optional; ensure it cannot cache private status/downloads or stale demo pages; disable registration for the assessed build if not verified |
| Dark mode and general profile styling | Existing header/theme actions | Low-cost to retain if already working; no redesign or new effort required |
| Two-factor setup | Auth/Security and setup view | Optional setup UI; do not weaken login/session/password protection while simplifying navigation |
| Bulk CSV import, backup UI, maintenance tools and announcements | Admin code/views and combined backup controller | Keep only needed administration; use the verified local recovery procedure regardless of optional backup UI |

## Removal decision

Recommended first step: keep upstream history/source, restrict the assessed workflow and navigation, and stop claiming/supporting unrelated extras. Hiding a link is a presentation choice, **not** endpoint disabling or a security control. If the team chooses actual disabling, add a small explicit route/action allowlist and test direct URLs as well as menus. Do not introduce a general plugin/feature-flag framework for this.

Deletion is a separate scope decision. Before deleting a module, inspect its callers, hooks, schema foreign keys, shared header counters and side effects. For example, submitting an assignment calls `Gamification::award(...)`; deleting its file without removing/replacing that call would break the retained workflow. Keep already-applied schema history and migrate forward if data shape must change.

This comparison supports a team decision. It does not expand the course project's requirements or assert that all these upstream features work.
