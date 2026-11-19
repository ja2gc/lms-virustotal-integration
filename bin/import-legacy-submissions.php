<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once dirname(__DIR__).'/config/config.php';
require_once dirname(__DIR__).'/config/database.php';
require_once dirname(__DIR__).'/app/scan/bootstrap.php';
require_once dirname(__DIR__).'/app/scan/ScanSubmissionService.php';
if (array_diff(array_slice($argv,1),['--apply'])) { fwrite(STDERR,"Usage: php bin/import-legacy-submissions.php [--apply]\n"); exit(2); }
try {
    $service=new ScanSubmissionService(Database::getInstance(),getenv('SCAN_PRIVATE_DIR')?:ROOT_DIR.'/private/submissions');
    $counts=$service->importLegacy(UPLOAD_DIR.'/submissions',in_array('--apply',$argv,true));
    echo json_encode(['mode'=>in_array('--apply',$argv,true)?'apply':'dry-run']+$counts,JSON_THROW_ON_ERROR).PHP_EOL;
    exit($counts['failed']>0?1:0);
} catch (Throwable) { fwrite(STDERR,"Legacy import failed. Verify the scan migration and documented storage directories. Original files are preserved.\n"); exit(1); }
