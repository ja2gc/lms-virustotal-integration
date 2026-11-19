<?php
declare(strict_types=1);

/** Disposable copy and real HTTP clients: never uses a member's SQLite DB. */
final class WebFixture
{
    public string $baseUrl;
    private $process;
    private array $cookies = [];
    private string $password;
    private string $directory;
    private array $environment;

    public function __construct(bool $installed = true)
    {
        $root = dirname(__DIR__, 2);
        $dir = $root . '/tmp/tests/web-' . bin2hex(random_bytes(6));
        $this->directory = $dir;
        $overrides = ['DOODLE_DB_TYPE'=>'sqlite', 'VT_API_KEY'=>'',
            'SCAN_MODE'=>'fixture', 'SCAN_PRIVATE_DIR'=>$dir.'/private/submissions'];
        $environment = getenv();
        // Windows variable names are case-insensitive: remove aliases before adding overrides.
        foreach (array_keys($environment) as $name) {
            if (array_key_exists(strtoupper($name), $overrides)) unset($environment[$name]);
        }
        $this->environment = array_merge($environment, $overrides);
        mkdir($dir . '/sessions', 0700, true);
        mkdir($dir . '/request-uploads', 0700, true);
        foreach (['app', 'config', 'database', 'public', 'bin'] as $folder) {
            if (!is_dir($root . '/' . $folder)) continue;
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $folder, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                if (!in_array(strtolower($file->getExtension()), ['php', 'css', 'js', 'json', 'png'], true)
                    || str_starts_with($relative, 'public/uploads/')) {
                    continue;
                }
                $target = $dir . '/' . $relative;
                if (!is_dir(dirname($target))) mkdir(dirname($target), 0700, true);
                copy($file->getPathname(), $target);
            }
        }
        mkdir($dir . '/public/uploads', 0700, true);
        foreach (['index.php', 'install.php', 'router.local.php'] as $name) copy($root . '/' . $name, $dir . '/' . $name);
        $this->password = bin2hex(random_bytes(18));
        $seed = <<<'PHP'
require 'config/config.php'; require 'config/database.php'; require 'app/helpers/functions.php';
require 'database/schema.php'; require 'database/schema_v2.php'; require 'database/schema_v3.php';
$db=Database::getInstance(); Schema::install($db,'sqlite'); SchemaV2::install($db); SchemaV3::install($db); Schema::seed($db);
$hash=password_hash($argv[1],PASSWORD_BCRYPT);
foreach(['admin','teacher','student','other_teacher','other_student'] as $i=>$name) {
    $role=str_replace('other_','',$name);
    Database::insert('users',['username'=>$name,'email'=>$name.'@example.test','password'=>$hash,'full_name'=>'Fixture '.$name,'role'=>$role,'status'=>'active']);
}
foreach([2,4] as $i=>$teacher) {
    $cid=Database::insert('courses',['title'=>'Fixture Course '.($i+1),'slug'=>'fixture-'.($i+1),'teacher_id'=>$teacher,'status'=>'published','visibility'=>'public']);
    Database::insert('enrollments',['course_id'=>$cid,'student_id'=>$i===0?3:5,'status'=>'active']);
}
foreach([[2,null,0],[2,1,0],[4,null,1],[5,null,0]] as $i=>[$owner,$course,$public]) {
    $id=Database::insert('flashcard_decks',['title'=>'Fixture Deck '.($i+1),'user_id'=>$owner,'course_id'=>$course,'is_public'=>$public]);
    Database::insert('flashcards',['deck_id'=>$id,'front'=>'Fixture question','back'=>'Fixture answer','sort_order'=>0]);
}
file_put_contents('.installed','fixture'); file_put_contents('.v3_migrated','fixture');
PHP;
        if ($installed) {
            $seedProcess = proc_open([PHP_BINARY, '-r', $seed, $this->password],
                [0 => ['pipe', 'r'], 1 => ['file', $dir . '/seed.log', 'a'], 2 => ['file', $dir . '/seed.log', 'a']], $pipes, $dir, $this->environment);
            if (!is_resource($seedProcess)) throw new RuntimeException('Cannot initialize disposable database.');
            fclose($pipes[0]);
            if (proc_close($seedProcess) !== 0) throw new RuntimeException('Disposable database setup failed; inspect ignored tmp/tests seed log.');
        }

        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        if ($socket === false) throw new RuntimeException('Cannot allocate HTTP fixture port.');
        $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);
        $this->baseUrl = 'http://127.0.0.1:' . $port;
        $this->process = proc_open([PHP_BINARY, '-d', 'session.save_path=' . $dir . '/sessions', '-d', 'upload_tmp_dir=' . $dir . '/request-uploads',
            '-d', 'upload_max_filesize=10M', '-d', 'post_max_size=12M', '-S', '127.0.0.1:' . $port, '-t', $dir, $dir . '/router.local.php'],
            [0 => ['pipe', 'r'], 1 => ['file', $dir . '/server.log', 'a'], 2 => ['file', $dir . '/server.log', 'a']], $pipes, $dir, $this->environment);
        if (!is_resource($this->process)) throw new RuntimeException('Cannot start HTTP fixture.');
        fclose($pipes[0]);
        for ($attempt = 0; $attempt < 40; $attempt++) {
            $connection = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
            if ($connection !== false) { fclose($connection); return; }
            usleep(50000);
        }
        throw new RuntimeException('HTTP fixture did not start.');
    }

    public function __destruct()
    {
        if (is_resource($this->process)) { proc_terminate($this->process); proc_close($this->process); }
    }

    public function request(string $user, string $route, ?array $post = null, ?array $attachment = null): array
    {
        $url = str_starts_with($route, 'http') ? $route : $this->baseUrl . '/index.php?r=' . $route;
        if (!str_starts_with($url, $this->baseUrl . '/')) throw new RuntimeException('Fixture request must stay local.');
        $headers = ['Cookie: ' . implode('; ', $this->cookies[$user] ?? [])];
        $content=$post===null?'':http_build_query($post);
        if ($attachment!==null) {
            $boundary='fixture'.bin2hex(random_bytes(12)); $content='';
            foreach ($post??[] as $key=>$value) $content.='--'.$boundary."\r\nContent-Disposition: form-data; name=\"".$key."\"\r\n\r\n".$value."\r\n";
            $content.='--'.$boundary."\r\nContent-Disposition: form-data; name=\"file\"; filename=\"".$attachment['name']."\"\r\nContent-Type: application/octet-stream\r\n\r\n".$attachment['bytes']."\r\n--".$boundary."--\r\n";
            $headers[]='Content-Type: multipart/form-data; boundary='.$boundary;
        } elseif ($post !== null) $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $context = stream_context_create(['http' => ['method' => $post === null ? 'GET' : 'POST',
            'header' => implode("\r\n", $headers), 'content' => $content,
            'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 6]]);
        $body = @file_get_contents($url, false, $context);
        preg_match('~^HTTP/\S+ (\d+)~', $http_response_header[0] ?? '', $match);
        $location = null;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('/^Set-Cookie: ([^=]+)=([^;]*)/i', $header, $cookie)) $this->cookies[$user][$cookie[1]] = $cookie[1] . '=' . $cookie[2];
            if (stripos($header, 'Location: ') === 0) $location = substr($header, 10);
        }
        return ['status' => (int) ($match[1] ?? 0), 'body' => $body === false ? '' : $body, 'location' => $location, 'headers' => $http_response_header ?? []];
    }

    public function login(string $user, ?string $password = null): void
    {
        $page = $this->request($user, 'auth/login');
        if ($page['status'] === 302) {
            $this->request($user, 'auth/logout');
            $page = $this->request($user, 'auth/login');
        }
        $result = $this->request($user, 'auth/login', ['identifier' => $user, 'password' => $password ?? $this->password, '_csrf_token' => self::csrf($page)]);
        if ($result['status'] !== 302) throw new RuntimeException('Fixture sign-in failed for ' . $user);
    }

    public static function csrf(array $page): string
    {
        if (!preg_match('/(?:name="_csrf_token" value="|_csrf_token=)([a-f0-9]+)/', $page['body'], $match)) throw new RuntimeException('Expected CSRF token in fixture page.');
        return html_entity_decode($match[1], ENT_QUOTES);
    }

    public function php(array $arguments): array
    {
        $process = proc_open(array_merge([PHP_BINARY], $arguments),
            [0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w']], $pipes, $this->directory, $this->environment);
        if (!is_resource($process)) throw new RuntimeException('Cannot run fixture PHP command.');
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        return [proc_close($process), $output];
    }

    public static function pageOk(array $page, string $context): void
    {
        if ($page['status'] !== 200 || preg_match('/(?:Fatal error|Uncaught \w+|<b>Warning<\/b>)/i', $page['body'])) {
            throw new RuntimeException($context . ': page status ' . $page['status'] . ', runtime error=' . (int) str_contains($page['body'], 'Fatal error'));
        }
    }
}
