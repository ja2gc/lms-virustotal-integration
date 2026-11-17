<?php
declare(strict_types=1);

return ['group' => 'routing', 'tests' => [
    'local server serves assets and routes without exposing project files' => function (): void {
        $root = dirname(__DIR__);
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        if ($socket === false) {
            throw new RuntimeException('Cannot allocate loopback test port.');
        }
        $address = stream_socket_get_name($socket, false);
        $port = (int) substr(strrchr($address, ':'), 1);
        fclose($socket);
        $logDir = $root . '/tmp/tests/server-' . bin2hex(random_bytes(6));
        mkdir($logDir, 0700, true);
        $process = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', $root, $root . '/router.local.php'],
            [0 => ['pipe', 'r'], 1 => ['file', $logDir . '/out.log', 'a'], 2 => ['file', $logDir . '/error.log', 'a']], $pipes, $root);
        if (!is_resource($process)) {
            throw new RuntimeException('Cannot start local PHP server.');
        }
        fclose($pipes[0]);
        try {
            $ready = false;
            for ($attempt = 0; $attempt < 40; $attempt++) {
                $connection = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
                if ($connection !== false) {
                    fclose($connection);
                    $ready = true;
                    break;
                }
                usleep(50000);
            }
            if (!$ready) {
                throw new RuntimeException('PHP server did not listen.');
            }
            foreach ([
                '/public/css/style.css' => 200,
                '/.git/config' => 404,
                '/config/config.php' => 404,
                '/database/doodle.sqlite' => 404,
                '/database/schema.php' => 404,
                '/docs/SRS.md' => 404,
                '/evidence/baseline/local-courses.jpg' => 404,
                '/tests/run.php' => 404,
                '/public/uploads/index.php' => 404,
                '/public/uploads/submissions/.gitkeep' => 404,
                '/install.php/extra' => 404,
                '/%2e%67it/config' => 404,
                '/public/%2e%2e/config/config.php' => 404,
                '/public/../config/config.php' => 404,
            ] as $path => $expected) {
                $context = stream_context_create(['http' => ['ignore_errors' => true, 'follow_location' => 0, 'timeout' => 4]]);
                $body = @file_get_contents('http://127.0.0.1:' . $port . $path, false, $context);
                preg_match('~^HTTP/\S+ (\d+)~', $http_response_header[0] ?? '', $match);
                $status = (int) ($match[1] ?? 0);
                $cssType = count(array_filter($http_response_header ?? [], fn(string $header): bool => stripos($header, 'Content-Type: text/css') === 0)) > 0;
                if ($status !== $expected || ($expected === 200 && ($body === false || !$cssType))) {
                    throw new RuntimeException($path . ': expected ' . $expected . ', got ' . $status . ($expected === 200 && !$cssType ? ' without CSS content type' : ''));
                }
            }
        } finally {
            proc_terminate($process);
            proc_close($process);
        }
    },
]];
