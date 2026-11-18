<?php
declare(strict_types=1);

final class ScanMigration
{
    public static function apply(PDO $db): void
    {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME)!=='sqlite') throw new RuntimeException('Scan migrations require the documented SQLite runtime.');
        $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA foreign_keys=ON');
        $db->exec('PRAGMA busy_timeout=5000');
        $db->exec('BEGIN IMMEDIATE');
        try {
            $db->exec('CREATE TABLE IF NOT EXISTS scan_schema_versions (version INTEGER PRIMARY KEY, applied_at TEXT NOT NULL)');
            if (!$db->query('SELECT 1 FROM scan_schema_versions WHERE version=1')->fetchColumn()) {
                $migration=require dirname(__DIR__,2).'/database/migrations/001_scan_foundation.php';
                $migration($db);
                $db->exec("INSERT INTO scan_schema_versions VALUES (1,strftime('%Y-%m-%dT%H:%M:%SZ','now'))");
            }
            $db->exec('COMMIT');
        } catch (Throwable $error) { $db->exec('ROLLBACK'); throw $error; }
    }
}
