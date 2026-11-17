# Validation and evidence plan

## Current evidence

The [original baseline PHP checks](../evidence/baseline/php-checks.json) showed 122 syntax passes and nine controller-load failures. The original failed installer check is preserved separately. Subsequent [localhost smoke evidence](../evidence/baseline/localhost-smoke.json) records the repaired startup, baseline/routing regressions and observed install/login/course/written-submission/grading/feedback browser journey. This is partial baseline coverage; attachment validation/replacement and complete UAT remain open. No VirusTotal test, load test, failure-injection/recovery result or full integration security assessment has passed yet.

The minimal `tests/run.php` harness now discovers baseline and routing checks, runs offline and exits nonzero on failure or no matching tests. Extend it with the planned groups rather than adding another runner. Integration checks must exercise real application logic with an injected provider transport and must not depend on PHP assertions being enabled. Provider/worker checks are still to implement.

## Required test catalogue

Requirements authority: [SRS](SRS.md), including FR01-FR26, NFR01-NFR12 and R01-R15. The catalogue below defines implementation/measurement checks. The [demo/manual UAT guide](demo-manual.md) separately defines full manual feature acceptance, its actual-result/defect log, and the short panel sequence.

| ID | Scenario and assertion |
| --- | --- |
| BT01 | Load every controller with the base class; no signature conflicts. Install isolated demo database; log in and navigate course/assignment pages. |
| BT02 | Teacher Grade link and form reach `gradeSubmission`; score/feedback persist and are visible to the correct student. |
| CT01 | Valid completed file analysis with matching analysis ID/hash -> expected normalized contract and advisory. |
| CT02 | Missing counter, negative/string counter, malformed JSON, wrong analysis ID or file hash, empty evidence and unknown status -> Unresolved. |
| CT03 | Malicious, suspicious-only, no-detection, failed-engine and unsupported-engine fixtures follow the exact policy. |
| CT04 | Both automatic and teacher-rescan use cases call the unchanged provider interface; adapter does not depend on SQL, sessions or original controller. |
| IT01 | Saved attachment yields one automatic attempt; no provider call occurs within submission HTTP request. |
| IT02 | Same hash in two submissions remains separate; replacement during polling prevents late result attachment to the new file. |
| IT03 | Duplicate POST token/job delivery, two competing claims and expired lease do not duplicate durable work or overwrite another lease's result. |
| IT04 | Worker restarts after analysis ID persisted; resumes reads without starting a second analysis. Unknown-outcome POST timeout is terminal/explicitly unresolved, not blind replay. |
| IT06 | Crash after `start_in_flight` is stored and before analysis ID persists -> start_outcome_unknown with no repeated POST. Quota deferral past persisted poll_deadline_at -> polling_exhausted with no further HTTP; pre-start quota delay remains waiting. |
| IT05 | Quota limiter accounts for analysis and identity reads, startup lookup and POST; actual HTTP starts stay within the configured rolling/minute and daily budgets. |
| FT01 | Inject timeout/network failure before and during provider reads. Saved submissions/files remain; recovery is scheduled and measured. |
| FT02 | Inject 401, 403, 404-by-step, both 429 error codes, 503, malformed body and invalid identity. Check distinct policies and final reasons. |
| FT03 | Stop worker, expire claim, restart; test SQLite lock/write failure and disk write failure. No lost submission, favorable false result or leaked file. |
| ST01 | Student A cannot read/download Student B's file/result. Teacher A cannot read/rescan Teacher B's course submissions. Unauthenticated access denied. |
| ST02 | CSRF rejected, rescan requires POST, forged file paths/IDs denied, MIME mismatch and oversized uploads rejected, failed move does not report success. |
| ST03 | Serving command denies direct `.git`, DB, configuration and private-file paths. Logs/status HTML/JSON contain no API key/cookie/raw file or provider body. |
| UI01 | Student and teacher screens show pending, retry, unresolved, malicious, suspicious and no-detection labels; grade state stays separate; narrow/keyboard/error states work. |
| UI02 | Course catalog/enrollment/materials, assignment calendar/deadlines, dashboards, announcements, notifications, profiles, submissions/grading/feedback remain reachable for correct roles. No quiz or excluded-module links appear in assessed menus, gradebook or dashboards. |
| ST04 | Excluded direct routes and quiz actions within retained controllers are denied. Hiding links alone cannot pass. Retained course/calendar/announcement/notification and new scan/download routes still enforce authorization. |
| LIVE01 | One limited real synthetic-file flow obtains a real analysis ID, retrieves evidence, and verifies file relationship. Record sanitized request types/status/times; redact credentials. |
| RESTORE01 | Restore a disposable copy of DB + private files; verify a harmless attachment's bytes/hash, ownership and advisory linkage. |

## Performance experiment

Load-test **your LMS** against the deterministic fake transport, not the public VirusTotal service. Environment record includes OS/CPU/RAM, PHP/extensions, DB engine, web-server command/configuration, worker count, source revision, fixture delays/file sizes and concurrent workload.

Increase one variable at a time: concurrent submitters at 1, 5, 10 and 20 using synthetic attachments of a fixed recorded size. Hold other settings constant; run a 30-second warm-up and a measured 120-second interval per level as initial experiment settings. Extend the ramp only if there is no observed degradation or resource saturation; do not manufacture a failure or report the highest tested load as a discovered breaking point.

Measure acceptance rate, throughput, submit p50/p95/p99, errors/timeouts, DB-lock errors, worker throughput, queue depth/age, queue drain time and resource use. Proposed acceptance target: p95 save latency <=2 seconds at 10 clients, excluding network transfer, with zero lost accepted submissions and zero false favorable advisories. Targets are hypotheses to validate, not achieved performance. Report queue saturation caused by the simulated provider limit separately from web submission capacity.

Record where latency/throughput first degrades and the failure mode (for example DB lock contention, timeouts or growing backlog). If none occurs, report the tested bound and that the degradation point was not reached. Provider response-time guarantees are outside the application team's control. LIVE01 demonstrates connectivity/contract only, never load capacity.

## Reliability experiment

For each timeout, server-error and malformed-response fault: record start/end time, affected attempt IDs, persisted state/reason, count of saved submissions, number of HTTP requests, recovery trigger, first retry, first successful valid result and total recovery duration. Run at least three trials per fault with identical fixtures; report each result and the range. Use controlled fake transport and clock where needed so a quota-window test need not consume a day.

For malformed evidence the desired recovery can be a terminal Unresolved outcome followed by an authorized new attempt; do not imply the system self-repaired an invalid contract. For authentication failure operator correction is part of the measured recovery path. Measure queue restart separately from provider restoration. Never count a cached old result as a fresh recovery.

## Evidence format

Store sanitized commands/configuration summaries and raw CSV/JSON/text under `evidence/contracts/`, `evidence/reliability/`, `evidence/performance/`, `evidence/security/`, `evidence/live/`, `evidence/uat/` and `evidence/release/` when those runs actually happen. Include a small manifest: FR/NFR/R and test/case IDs, time, source revision, environment, invocation/steps, expected behavior, actual observed behavior, status and linked raw output. For manual cases also record tester, fixture/live/operator-assisted mode, defect ID, owner and retest reference. Screenshots supplement structured evidence; they do not replace measurements. Do not commit credentials, real identities, local DBs, backups or uploads.

Before release, trace every R-ID to implementation and evidence; explain any failed/untested requirement rather than relabeling it as complete.

## Full manual acceptance and regression

Run UAT01-UAT34 and each named variant on the integrated release, including the retained course/material/calendar/profile/announcement/notification and assignment/grading paths. A short presentation is not a complete manual test run. Keep statuses Not run, Pass, Fail or Blocked; record actual results rather than copying expected text. Do not prefill pass records during development.

Retain failed results and defect reports, then add retest results on the fixed revision and rerun affected producer/consumer/role cases. Route/authorization or private-file failures are not cosmetic issues. Team-run manual acceptance is not independent stakeholder sign-off unless a real stakeholder participates and accepts it. Backend concurrency, reusable-call-site, quota and load claims still need the focused automated/measurement checks above.

## Team handoff verification

Producer and consumer run the same fixture/JSON examples before merge. Check application camelCase versus SQL columns, nullability, exact status strings, seconds/bytes/UTC, identity types and normalized errors. Both reusable consumers exercise the same classifier. Test rescan token replay returns the original attempt and actual state, and result/lifecycle writes are atomic under the lease. Each stream adds focused test files to the common discovery runner. Final proof is an integrated role workflow and actual provider evidence, not four independently green modules.
