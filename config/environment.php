<?php
declare(strict_types=1);

final class LocalEnvironment
{
    /** Load simple INI-style KEY=value settings; never execute a local configuration file. */
    public static function load(string $file): void
    {
        if (!is_file($file)) return;
        $settings=@parse_ini_file($file,false,INI_SCANNER_RAW);
        if ($settings===false) throw new RuntimeException('Invalid local environment configuration.');
        $allowed=['VT_API_KEY','SCAN_MODE','SCAN_PRIVATE_DIR','SCAN_MAX_UPLOAD_BYTES',
            'SCAN_REQUESTS_PER_MINUTE','SCAN_REQUESTS_PER_DAY','DOODLE_ENV','DOODLE_DB_TYPE',
            'DOODLE_DB_HOST','DOODLE_DB_PORT','DOODLE_DB_NAME','DOODLE_DB_USER','DOODLE_DB_PASS'];
        foreach ($allowed as $name) {
            if (!array_key_exists($name,$settings)) continue;
            if (!is_string($settings[$name])) throw new RuntimeException('Invalid local environment configuration.');
            // Explicit process settings (including an empty value) take precedence.
            if (getenv($name)===false) putenv($name.'='.$settings[$name]);
        }
    }
}
