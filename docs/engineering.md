# Engineering and security

Existing-code notes describe the inspected baseline. Current controls and executed checks are recorded below and in the validation guide. Step C owns verification/evidence.

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

The [supported action matrix](SRS.md#cleanup-action-matrix) defines the public interface: courses/materials/enrollment, assignments/grading, dashboards, calendar, notices and profiles.

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

**Serving controls:** `router.local.php` blocks private/source/configuration/database/test/dot paths. `config/routes.php` applies the supported action allowlist before loading controllers. Object authorization and file-ID downloads are exercised by access and connected tests. Status reads never start provider work.

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

Use fictional accounts and synthetic, non-sensitive attachments only. Neither consent wording nor hash-first lookup makes public scanning suitable for confidential coursework. No real student work or PII is sent or queried. An initial small upload limit of **10 MiB (10,485,760 bytes)** and allowlist of **PDF, TXT and DOCX** are implemented limits, with MIME validation and aligned browser/application/PHP settings. Other upstream lesson uploads are outside the assessment scope; the demo must not expose the installation beyond loopback.

Source: [VirusTotal upload documentation and data-sharing warning](https://docs.virustotal.com/reference/files-scan). Direct file upload has its own documented limit; this smaller application limit is a team policy, not a provider requirement.

## Secrets and local serving

Manual flashcards now check deck owner/public/course scope before viewing/studying/reviewing. Course-deck creation checks accessible courses; review updates require the current user's review record and current deck access. Delete is a CSRF-protected POST for owner/admin. Failed study requests keep visible progress unchanged. Integration boundaries have their own connected checks. Public synthetic localhost demo credentials are an explicitly authorized exception; real credentials/provider keys remain prohibited.

`config/config.php` loads the root ignored `.env` through `config/environment.php` before reading configuration. The loader accepts allowlisted simple INI-style values without executing/interpolating them and keeps existing process settings in priority. Consumers read `getenv('VT_API_KEY')`; no browser bundle, settings row or committed file contains the key. The tracked `.env.example` has an empty key and fixture mode. Agents must not inspect real `.env` contents or log credentials/HTTP headers. Members enter their own key locally following README; Git ignores real environment variants and release packaging excludes them. The class-only scan bootstrap still starts no session and reads no environment file; CLI entry points load shared configuration explicitly.

PHP's built-in server does not enforce Apache `.htaccess`. The implemented local router blocks `.git`, configuration, private storage, database, documentation and evidence paths, and exposes intended entry points and allowlisted static assets. The public action map, private upload/backfill, authorized downloads and file-ID links pass connected checks. Bind to `127.0.0.1`, not all network interfaces. Private files belong outside the served root or are denied by a tested router, with no alternate static path. Do not rely on an `index.php` returning 404 to protect sibling files.

Private responses use no-store headers. Browser status polling reads persisted results; fixture provenance remains visible after configuration changes.

No automatic deletion of submissions, grading changes or favorable advisory follows from a provider result. Treat the output as evidence at a particular time. Warn on unresolved/flagged findings; never execute uploaded content on the server. The project does not certify attachments as safe.

## Required proof and release review

Exercise wrong-role and cross-object reads, downloads and rescan POSTs; CSRF rejection; path traversal; MIME mismatch; oversized/upload failure; malformed provider replies; key redaction; static access to protected paths; and late-response ownership. Test against the actual local serving command. Record each implemented control, command, result and accepted residual risk here at release. Until then, residual risks are identified, not accepted on the team's behalf.

## Integrated implementation

`ScanSubmissionService` replaces public assignment uploads with immutable private revisions; `StudentController` preserves input and prior files on rejected replacements. Use the README server command for writable upload/session temporary directories and the 10M/12M PHP limits. DOCX validation uses the built-in Phar module and bounded temporary ZIP inspection; it adds no package dependency.

The two worker consumers share one adapter and durable metered transport. Result reads reserve at most two calls, validate leases/deadlines before each request and release unused capacity. Unknown scan-start outcomes terminate without replay. The worker CLI supports fixture scenarios and explicit operator resume.

Student/teacher views use private file-ID downloads, advisory/history partials and same-origin status polling. The browser pauses polling while hidden, backs off refresh errors and stops at terminal states. Uncertain rescan requests retain their operation token for idempotent replay. Failed/unknown state never displays a favorable advisory.

Executed checks: `php tests/run.php`, three Node checks, `php tests/reliability/runner.php --write`, and disposable native-browser inspection. Load runner: `php tests/load/submissions.php`; outputs and environment are recorded in its summary. These runs do not replace unexecuted manual acceptance or independent setup. See the [validation guide](final-project/step-c-performance-reliability-security-validation.md).
