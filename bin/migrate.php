<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once dirname(__DIR__).'/config/config.php';
require_once dirname(__DIR__).'/config/database.php';
require_once dirname(__DIR__).'/app/scan/bootstrap.php';
try {
    ScanMigration::apply(Database::getInstance());
    echo "Scan schema version 1 ready. Legacy attachment import is a separate Member 1 task.\n";
} catch (Throwable) {
    fwrite(STDERR,"Scan migration failed; verify the documented SQLite installation and baseline schema.\n");
    exit(1);
}
