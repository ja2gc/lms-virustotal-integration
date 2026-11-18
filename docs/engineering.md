# Engineering and security

Existing-code notes describe the inspected baseline. Integration UI/security controls are the target unless executed evidence is cited; they do not claim VirusTotal/private downloads exist. Step C owns verification/evidence.

## Existing patterns to reuse

Doodle is PHP with server-rendered views, PDO helpers and vanilla CSS/JavaScript. It is not React: no React hooks, component library or frontend build pipeline is needed for this feature.

The baseline renderer is now `Controller::render($view, $data)`. Use it for layout rendering; keep public `view(int)` actions for their existing URLs. `viewRaw()` and the global error-view helper retain their names. Teacher detail URLs use `teacher/editCourse/{id}`, `teacher/gradeSubmission/{id}` and `teacher/gradebookCourse/{id}`. Sidebar role dashboards match only their own index routes; course edit/assignment/grading belong under My Courses. See [baseline evidence](final-project/step-c-performance-reliability-security-validation.md#baseline-verification).

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

Read [approved features](SRS.md#cleanup-action-matrix): retain courses/materials/enrollment, assignments/deadlines, grading/feedback, dashboards, announcements, in-app notifications, existing calendar and basic profile/theme behavior. Exclude quiz creation/taking/auto-grading and the listed unrelated modules. No source removal has occurred in this planning session.

Check [file ownership](team-plan.md#ownership) before editing shared code or interfaces.

- Submission confirmation means saved, with a separate scan-pending display. No attachment means Not applicable. Preserve fields when validation fails.
- Show queued, checking, waiting/retrying, completed or unresolved processing information separately from the four advisory labels. A completed check can be inconclusive.
- Student sees only their submission's current file revision and permitted result summary. Teacher/admin sees authorized review details, counts, checked time, prior attempts and a rescan action. Never show a previous file's result as the replacement's result.
- Poll status every 15 seconds while active, pause when the page is hidden, stop after a terminal state, and back off after transient errors. Browser polling never triggers provider calls. A failed refresh keeps the last known display with a refresh error; it does not reset the advisory.
- Rescan is a CSRF-protected POST with a server-issued idempotency token; disable repeated clicking while submitting but enforce deduplication on the server too. State clearly when a new check is pending.
- Downloads are authenticated attachments, not inline previews or public storage URLs. Show advisory warnings near the action without implying a guarantee of safety.
- Verify keyboard access, persistent labels, narrow screens, long names, empty/error states and text enlargement. Keep the upstream visual language and familiar navigation.

## Test harness

The shared runner discovers tests/*Test.php. Each file returns `['group'=>'<group>', 'tests'=>['name'=>callable]]`. `php tests/run.php --group=<group>` filters groups and exits nonzero on failure or no tests. Add separate test files rather than competing edits to the runner. Baseline/routing/flashcards groups exist; adapter/worker/persistence/ui/status/access are added with their implementations. The optional JavaScript check uses Node standard libraries; Node is not an LMS runtime requirement.

## Security controls

The boundary table specifies target controls. Reassess each against final code and attach evidence before submission.

**Baseline serving control implemented:** `router.local.php` denies source/configuration/database/documentation/evidence/test/dot paths and arbitrary PHP entry points; its automated HTTP checks pass with the documented loopback command. See [baseline results](../evidence/baseline/localhost-smoke.json). Existing uploads remain public pending P1/P5. P0b now enforces the explicit SRS action allowlist in `config/routes.php` before loading controllers; excluded actions and aliases return 404. This does not satisfy private-download authorization or the full security assessment below.

Apply [approved assignment-only scope](SRS.md#cleanup-action-matrix) through explicit allowed actions as well as menus. Deny quiz actions on the retained Teacher controller. Preserve authorized calendar, announcements, notifications and course-material actions; scope reduction must not become an access-control bypass or unapproved removal. P0b/P5 own route/access controls; P4 owns navigation/templates; both verify ST04.

## Boundary assessment

| Integration point | Authentication / authorization | Transport | Input / output control | Logging and residual risk |
| --- | --- | --- | --- | --- |
| IP01 submission | Session, active student enrollment, CSRF, correct assignment | Loopback HTTP only | Enforce size/type, real MIME, upload error and move result; random private filename; validate written response and assignment state | Log IDs and reason, no contents; server-side file validation does not detect all malicious documents |
| IP02 durable work | Host service only; verify revision belongs to submission | Local PDO/OS permissions | Transaction, uniqueness and pointers validated together | Correlate operation/revision/attempt; local machine compromise can read the demo DB |
| IP03 hash lookup | Worker-only key; fixed provider origin | Verified TLS | Exact hash; no arbitrary URL or redirects with credentials | Endpoint type/status/duration only; querying a hash discloses an indicator |
| IP04 upload | Worker key; synthetic-file policy | Verified TLS | Private storage reference resolved by application, size cap, no user-selected filesystem path | No bytes/name/key in logs; uploaded content may become accessible to the VT community |
| IP05 rescan | Authorized host operation, worker-held key | Verified TLS | Expected hash, bounded request budget | Record request/attempt origin; ambiguous POST may have created upstream work |
| IP06 analysis | Worker key; analysis ID from durable host record | Verified TLS | ID length/encoding, JSON/schema/counter validation; fixed origin; bounded response body | Record normalized reasons only; provider evidence can be incomplete or mistaken |
| IP07 identity | Same provider boundary | Verified TLS | Require file object and exact expected SHA-256 | Mismatch -> Unresolved; extra request consumes quota |
| IP08 persistence | Worker process; lease ownership plus correct attempt/revision | Local PDO | Conditional updates, prepared statements, guarded current pointers | Record transitions; data remains on the local machine and needs a recoverable copy |
| IP09 status | Session plus owner/course-teacher/admin check for every ID | Loopback HTTP | Allowlisted response fields; no paths/raw JSON; cache private/no-store | Record denied object access without disclosing its content; numeric IDs are enumerable but authorization must still deny access |
| IP10 rescan request | Teacher owns course or admin; session and CSRF | Loopback HTTP | POST only, current file exists, idempotency token, request throttling | Actor/attempt/action/outcome; authorized users can still consume finite shared quota |
| IP11 download | Same object ownership and teacher scope, checked before read | Loopback HTTP | Resolve known storage key under private root; attachment disposition, nosniff, escaped display filename; no static alias | Log file ID and outcome, not disk path; downloading/opening an advisory file remains a user risk |

## Local demonstration policy

Use fictional accounts and synthetic, non-sensitive attachments only. Neither consent wording nor hash-first lookup makes public scanning suitable for confidential coursework. No real student work or PII is sent or queried. An initial small upload limit of **10 MiB (10,485,760 bytes)** and allowlist of **PDF, TXT and DOCX** are proposed defaults; the first contract PR must verify MIME mappings and align browser, application and PHP limits. Other upstream lesson uploads are outside the assessment scope; the demo must not expose the installation beyond loopback.

Source: [VirusTotal upload documentation and data-sharing warning](https://docs.virustotal.com/reference/files-scan). Direct file upload has its own documented limit; this smaller application limit is a team policy, not a provider requirement.

## Secrets and local serving

Manual flashcards now check deck owner/public/course scope before viewing/studying/reviewing. Course-deck creation checks accessible courses; review updates require the current user's review record and current deck access. Delete is a CSRF-protected POST for owner/admin. Failed study requests keep visible progress unchanged. This is scoped flashcard proof, not certification of the still-pending integration boundaries. Public synthetic localhost demo credentials are an explicitly authorized exception; real credentials/provider keys remain prohibited.

The application loads the API key from the process environment, never a browser bundle, database settings page or committed file. Provide a safe example with placeholder values; never open or commit a real `.env`. A CLI bootstrap reads environment configuration without starting browser sessions or running web migrations. Never log HTTP request headers.

PHP's built-in server does not enforce Apache `.htaccess`. The implemented local router blocks `.git`, configuration, private storage, database, documentation and evidence paths, and exposes intended entry points and allowlisted static assets. P0b scope/action restrictions are implemented; private-file authorization remains pending P1/P5. Bind to `127.0.0.1`, not all network interfaces. Private files belong outside the served root or are denied by a tested router, with no alternate static path. Do not rely on an `index.php` returning 404 to protect sibling files.

Do not add PWA, offline or two-factor features. P0b removed offline registration, retires the old worker and Doodle caches, and sends private/no-store headers on front-controller responses including logout. HTTP logout denial and worker retirement logic pass automated checks. A real upgrade from an already installed historical worker remains unexecuted; private-download behavior remains P5 work. See [cleanup evidence](../evidence/cleanup/p0b.json).

No automatic deletion of submissions, grading changes or favorable advisory follows from a provider result. Treat the output as evidence at a particular time. Warn on unresolved/flagged findings; never execute uploaded content on the server. The project does not certify attachments as safe.

## Required proof and release review

Exercise wrong-role and cross-object reads, downloads and rescan POSTs; CSRF rejection; path traversal; MIME mismatch; oversized/upload failure; malformed provider replies; key redaction; static access to protected paths; and late-response ownership. Test against the actual local serving command. Record each implemented control, command, result and accepted residual risk here at release. Until then, residual risks are identified, not accepted on the team's behalf.
