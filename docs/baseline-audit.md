# Doodle baseline audit

## Provenance and scope

Upstream: https://github.com/deathlegionteamlk/doodle

Inspected source revision: `6e5365ed49199bfceec61f9b3ba41d83a94ccb3d`. This identifies the audit input, not a claim that later revisions behave the same. Preserve the upstream history and `LICENSE`.

146 tracked files were inventoried. A case-insensitive tracked-source search for VirusTotal, antivirus, virus scanning, ClamAV, malware, quarantine and related provider terms found no matches. The submission flow and schema were inspected separately. No scanning integration was found.

Local PHP: 8.3.30, with PDO SQLite, PDO MySQL, cURL, mbstring, fileinfo and OpenSSL available. All 122 PHP files passed individual syntax checks. Loading each of the 26 controller files with its base class failed for nine files. See [raw normalized evidence](../evidence/baseline/php-checks.json). Constructors were not invoked; these are class-compatibility checks, not browser tests.

## Findings that affect the project

| ID | Evidence | Consequence / required work |
| --- | --- | --- |
| B01 | `Controller::view(string, array)` conflicts with public `view(int)` actions in Assignment, Course and seven other controller files | PHP fatal error when affected class loads. Rename the base rendering helper and its internal callers consistently; retain public view routes. Add a controller-load regression check. |
| B02 | Teacher views link/post to `teacher/grade/{id}`; implemented action is `gradeSubmission(int $id)` | Align grade links/form action with the callable action and verify GET plus POST. |
| B03 | Submission files are stored under `public/uploads/submissions`; views use `uploadUrl()` | The planned private-file boundary requires storage outside the web root and an authorized download endpoint; a 404 index file does not protect individual static files. |
| B04 | `StudentController::submitAssignment()` checks extension and size but does not call existing `Security::verifyUploadMime()` and does not check the move result | Integrate MIME checking and reject failed/invalid uploads before success is reported. Earlier review found the helper; it did not establish enforcement on this upload path. |
| B05 | Resubmission overwrites file fields on one submission row | Add revision identity and preserve scan-attempt linkage; hash alone cannot identify a submission or a new attempt. |
| B06 | No scan tables, job worker or VirusTotal call found in the submission workflow | Integration remains new group work. Existing email-queue schema is not evidence of a scan-job runner. |
| B07 | `install.php` calls `Session::start()` before explicitly loading `Session.php`; autoload setup is in `index.php` | Installer bootstrap is a source-level concern requiring execution in an isolated test. Do not advertise installation as verified. |
| B08 | Upstream README, release notes and source do not consistently describe the same version/features | Treat source as the baseline and test the scoped workflow. Do not copy upstream security or readiness claims as evidence. |
| B09 | Upstream links use `flashcards`/`messages`/`certificates` while the controller filenames/classes are singular; other combined-file modules are not discoverable by their route names | These are pre-existing routing gaps, not evidence that modules were removed. Approved excluded modules still need coordinated menu/action removal in P0b/P4. Manual flashcards were discussed again but have not been approved into scope; route and private-deck permission repairs plus tests are needed before retention. |

## Baseline gate

Before integrating features, verify: installation, login for each role, course enrollment, assignment detail, valid/invalid upload, replacement file, teacher submission list, grading and student feedback. Repair only defects blocking these paths. Reproduce B07 before selecting a fix. Inventory any additional blockers in the first PR.

The original documentation-only foundation did not repair application code or establish a working website. A subsequent localhost task reproduced the installer failure in the built-in preview; [the original localhost check](../evidence/baseline/localhost-check.json) preserves that failure before repairs.

## Local baseline repair and verification — 2026-11-18

B01/B02/B07 were repaired: the internal renderer is now `Controller::render()` (public `view(int)` routes remain), the installer explicitly loads Session, and grading links/forms use `gradeSubmission`. Browser testing also found nested course-edit and course-gradebook URLs that did not match callable actions; callers/redirects now use `editCourse` and `gradebookCourse`. The sidebar's role-dashboard prefix match highlighted Teacher Studio together with My Courses; exact dashboard matches and course-detail grouping fix it. Student/admin dashboard matches received the same correction.

Added `router.local.php`, a tested loopback startup command with a writable local session folder, `runtime-requirements.json`, `.v3_migrated` ignore and an offline baseline/routing test harness. No provider, worker, new scan schema or scope-removal code was added. B03-B06 remain open.

The [repaired-baseline evidence](../evidence/baseline/localhost-smoke.json) records executed checks and limits. In-app browser verification covered a new SQLite install, admin/teacher/student login, fictional user creation, course create/edit/publish, catalog/enrollment, written assignment submission, teacher list/grading and student score/feedback. The final [My Courses preview](../evidence/baseline/local-courses.jpg) shows only that sidebar section selected.

The full P0 gate is **partial**: valid/invalid attachment upload, replacement file and all remaining manual cases still need verification and the planned strengthening. A written-only submission is not file-upload acceptance. No live VirusTotal request, private-file guarantee, final load/failure/security assessment or second-machine reproduction is claimed.
