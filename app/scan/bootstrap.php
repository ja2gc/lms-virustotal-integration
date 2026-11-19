<?php
declare(strict_types=1);
// Explicit loading for web and CLI; no session, configuration, migration or job side effects.
require_once __DIR__.'/FileScanProvider.php';
require_once __DIR__.'/VirusTotalAdapter.php';
require_once __DIR__.'/CurlTransport.php';
require_once __DIR__.'/ScanMigration.php';
require_once __DIR__.'/ScanAttemptRepository.php';
require_once __DIR__.'/ScanAttemptService.php';
require_once __DIR__.'/ScanSubmissionService.php';
require_once __DIR__.'/ScanProviderBudget.php';
require_once __DIR__.'/ScanProviderClient.php';
require_once __DIR__.'/FixtureScanTransport.php';
require_once __DIR__.'/ScanResultProcessor.php';
require_once __DIR__.'/AutomaticSubmissionResult.php';
require_once __DIR__.'/TeacherRescanResult.php';
require_once __DIR__.'/ScanWorker.php';
