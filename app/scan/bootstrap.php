<?php
declare(strict_types=1);
// Explicit loading for web and CLI; no session, configuration, migration or job side effects.
require_once __DIR__.'/FileScanProvider.php';
require_once __DIR__.'/VirusTotalAdapter.php';
require_once __DIR__.'/CurlTransport.php';
require_once __DIR__.'/ScanMigration.php';
require_once __DIR__.'/ScanAttemptRepository.php';
require_once __DIR__.'/ScanAttemptService.php';
