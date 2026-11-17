# Approved website scope and workflow

The coordinator approved an assignment-focused LMS with VirusTotal integration. Quizzes are excluded, including quiz creation, quiz-taking and quiz auto-grading. Assignment scores, teacher feedback and the student gradebook remain in scope. This is the target feature set; no upstream module has yet been deleted or disabled in code.

Comparison is based on the supplied Technical 2 and revised Technical 3. Technical 1/4 were not supplied. Source presence is not proof of a working feature; existing paths must pass baseline smoke checks.

## Retain and verify

| Area | Approved behavior | Work boundary |
| --- | --- | --- |
| Accounts | Student, teacher and admin login/roles; basic profiles | Reuse existing auth and verify object permissions |
| Courses | Catalog, enrollment, basic lessons and learning materials | Reuse the existing teaching context; no course-engine redesign |
| Assignments | Instructions, deadlines, written/file submissions and resubmission | Primary assessment workflow; strengthen upload handling |
| Grading | Teacher scores/feedback and student assignment gradebook | Keep assignment grading; remove quiz-specific panels/links from the assessed interface |
| Communication | Announcements and in-app notifications | Reuse existing behavior; do not add external email or messaging |
| Organization | Role dashboards and existing assignment calendar | Retain useful existing views; no new calendar integration or expanded analytics |
| Convenience | Basic profiles and existing theme switch | Preserve if working; no redesign or new customization work |

If a retained supporting feature fails its smoke check, report the blocker and agree the smallest repair or deferment. Do not independently remove an approved feature or expand its implementation.

## Implement the missing integration

| New capability | Required result |
| --- | --- |
| Automatic VirusTotal analysis | Save submission first, then process its attachment asynchronously |
| Advisory display | Separate processing state from Flagged, Review Recommended, No Detections Reported and Unresolved |
| Teacher-requested fresh rescan | Create a new attempt and obtain a new analysis ID through the same result component |
| Correct result ownership | Bind evidence to the exact submission/file revision/current attempt; late results cannot replace newer evidence |
| Reliability and quota handling | Durable work, bounded read retry, shared pacing, explicit unresolved failures, restart recovery |
| Protected attachments | Private storage and authorized downloads; provider failures do not delete saved submissions |
| Minimal scan history/details | Teacher sees relevant counts, reason, time and prior attempts inside review; no separate analytics product |

The reusable result component has two real consumers: automatic submission processing and teacher-requested processing. See [contracts](integrations.md) and [implementation plan](implementation-plan.md).

## Exclude from the assessed interface

| Area | Upstream source | Approved treatment |
| --- | --- | --- |
| Quizzes | `QuizController.php`, teacher quiz actions/views | Exclude creation, taking, auto-grading and quiz-specific gradebook/dashboard content |
| AI study tools | `AIController.php`, `AIEngine.php`, AI views | Exclude chatbot, summaries and generated study/practice content |
| Gamification and flashcards | Gamification/Flashcard controllers, core helper, views | Exclude points/badges/streaks/leaderboard and flashcard UI |
| Social features | Message/Forum controllers and views | Exclude direct messages and discussion forums |
| Course extras | Certificate/Poll/LearningPath controllers and views | Exclude certificates, polls/surveys and multi-course learning paths |
| Collaboration/programming | Whiteboard/Code controllers; Wiki/Group classes in combined file | Exclude whiteboards, wiki/group workflows and code exercises |
| Extended analytics | `AnalyticsController.php` and views | Exclude engagement dashboards; required integration measurements remain deliverables |
| Generic API tokens | `ApiController.php` and token-management view | Exclude generic consumer/mobile API UI; retain new session-protected scan/status routes |
| Reviews/study utilities | `ReviewController.php` and views | Exclude ratings, personal notes, bookmarks and global study search |
| Extra external providers | OAuth, email and push code/schema | Leave OAuth/email/push unconfigured; VirusTotal is the only assessed external provider |

No new PWA/offline feature or two-factor implementation is planned. Preserve existing login/session/password/CSRF protection. If the upstream service worker could cache private or stale responses, repair its behavior or disable that registration with a documented security reason; do not claim offline support without testing it.

## How scope reduction is implemented later

Remove excluded menu/dashboard links and unnecessary header queries, and deny excluded direct routes/actions through a small explicit action allowlist. Hiding is not endpoint disabling. The route owner and UI owner use the same approved feature list. Verify that retained courses, assignments, calendar, announcements, notifications, profiles and grading remain reachable.

Do not bulk-delete upstream files or schema history. Trace callers first: assignment submission currently calls `Gamification::award(...)`. Source files/shared helpers may remain internally while their excluded UI/routes are disabled. Avoid a generalized plugin/feature-flag framework. Any further deletion or scope change needs a concrete proposal before editing.

## User workflows

**Teacher:** log in -> create course/materials -> publish assignment and deadline -> review submissions/advisories -> inspect counts/history or request rescan -> download an authorized attachment -> grade and give feedback.

**Student:** log in/enroll -> view materials and deadlines -> submit text/file -> receive saved confirmation and pending scan -> view advisory -> resubmit where allowed -> read grade/feedback and in-app notifications.

**Admin:** manage the fictional local users, roles, courses, announcements and basic settings needed to support that workflow. Provider credentials remain server-side, not a new admin settings form.

**Background:** worker claims durable work -> requests a new VirusTotal analysis -> reads and validates evidence -> checks file identity -> stores guarded result. Outage or malformed evidence preserves the submission and produces waiting/Unresolved behavior, never an invented favorable advisory.

## Schedule and demonstration

Finish target: **November 20, 2026**. Working presentation/submission date: **November 21, 2026**, inferred from the coordinator's November correction; check the class notice because the message still contained October 21 for the final date.

Proposed sequence: Nov 18 baseline/common-contract handoff; Nov 19 parallel module work and first integrated workflow; Nov 20 complete required evidence/documentation, freeze features and rehearse. This is a planning target, not a guarantee that unimplemented work will meet it.

Recommended demonstration: teacher publishes an assignment -> student submits -> advisory appears -> teacher requests rescan/reviews/grades -> student sees feedback. Then show one injected failure retaining the submission, correct replacement-file linkage and a denied cross-user read. Clearly label fixtures and show sanitized real VirusTotal evidence. Mention retained supporting features briefly rather than demonstrating every page. Confirm the allotted presentation duration before fixing timings.

PHP 8.3.30 is the only runtime checked so far. PHP 8.5 compatibility is an optional baseline investigation, not an approved upgrade or deadline prerequisite. Freeze one supported, patched and tested runtime for all members. No PHP installation was changed in this planning session.
