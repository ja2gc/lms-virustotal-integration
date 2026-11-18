<?php
/** Loopback development router. Start with the command in README.md. */
declare(strict_types=1);

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$path = preg_replace('~/+~', '/', $path);
$notFound = static function (): void {
    http_response_code(404);
    echo 'Not found';
};

// The built-in server ignores .htaccess: never expose source or dot paths.
if (str_contains($path, "\0") || str_contains($path, '\\')
    || preg_match('~(?:^|/)\.[^/]*|^/(?:app|config|database|docs|evidence|tests|tmp|temp|vendor|bin|private)(?:/|$)|^/public/uploads/submissions(?:/|$)~i', $path)) {
    $notFound();
    return;
}

if (str_starts_with($path, '/public/')) {
    $file = realpath(__DIR__ . $path);
    $public = realpath(__DIR__ . '/public') . DIRECTORY_SEPARATOR;
    // Windows short names and filesystem aliases must not bypass the URL guard.
    $submissions = realpath(__DIR__ . '/public/uploads/submissions');
    if ($file !== false && $submissions !== false
        && ($file === $submissions || str_starts_with(strtolower($file), strtolower($submissions) . DIRECTORY_SEPARATOR))) {
        $notFound();
        return;
    }
    $types = [
        'css' => 'text/css', 'js' => 'application/javascript', 'json' => 'application/json',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
        'pdf' => 'application/pdf', 'txt' => 'text/plain',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mp3' => 'audio/mpeg', 'wav' => 'audio/wav',
    ];
    $type = $types[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;
    if ($file === false || !str_starts_with($file, $public) || !is_file($file) || $type === null) {
        $notFound();
        return;
    }
    header('Content-Type: ' . $type);
    header('X-Content-Type-Options: nosniff');
    // Retained course materials stay public; assignment attachments are denied above.
    if (str_starts_with($path, '/public/uploads/')) {
        header('Content-Disposition: attachment');
    }
    readfile($file);
    return;
}

if ($path === '/install.php') {
    $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/install.php';
    require __DIR__ . '/install.php';
    return;
}

// Other source files and PHP path-info requests are never web entry points.
if (str_contains($path, '.')) {
    if ($path !== '/index.php') {
        $notFound();
        return;
    }
}
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/index.php';
require __DIR__ . '/index.php';
