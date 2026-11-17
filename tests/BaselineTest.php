<?php
declare(strict_types=1);

function baselineProcess(array $command, string $cwd): array
{
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start PHP check.');
    }
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return [proc_close($process), $output];
}

return ['group' => 'baseline', 'tests' => [
    'every controller can load with its base class' => function (): void {
        $root = dirname(__DIR__);
        $failures = [];
        foreach (glob($root . '/app/controllers/*.php') as $file) {
            [$exit, $output] = baselineProcess([PHP_BINARY, '-r',
                'require ' . var_export($root . '/app/core/Controller.php', true) . '; require ' . var_export($file, true) . ';'], $root);
            if ($exit !== 0) {
                $failures[] = basename($file);
            }
        }
        if ($failures) {
            throw new RuntimeException('Class loading failed: ' . implode(', ', $failures));
        }
    },
    'installer GET renders a form without an installed database' => function (): void {
        $root = dirname(__DIR__);
        $sandbox = $root . '/tmp/tests/installer-' . bin2hex(random_bytes(6));
        foreach (['config', 'app/helpers', 'app/core', 'sessions'] as $directory) {
            mkdir($sandbox . '/' . $directory, 0700, true);
        }
        foreach (['install.php', 'config/config.php', 'config/database.php', 'app/helpers/functions.php', 'app/core/Session.php'] as $file) {
            copy($root . '/' . $file, $sandbox . '/' . $file);
        }
        $code = '$_SERVER["REQUEST_METHOD"]="GET"; $_SERVER["SCRIPT_NAME"]="/install.php"; $_SERVER["HTTP_HOST"]="127.0.0.1"; require "install.php";';
        [$exit, $output] = baselineProcess([PHP_BINARY, '-d', 'session.save_path=' . $sandbox . '/sessions', '-r', $code], $sandbox);
        if ($exit !== 0 || !str_contains($output, '<form') || str_contains($output, 'Fatal error')) {
            throw new RuntimeException('Installer did not render its setup form: ' . substr(strip_tags($output), 0, 350));
        }
        if (file_exists($sandbox . '/.installed')) {
            throw new RuntimeException('Installer GET unexpectedly installed a database.');
        }
    },
    'teacher grade links and form resolve to a public grading action' => function (): void {
        $root = dirname(__DIR__);
        $code = <<<'PHP'
require 'config/config.php'; require 'app/helpers/functions.php';
require 'app/core/Session.php'; require 'app/core/CSRF.php'; require 'app/core/Auth.php';
require 'app/core/Controller.php'; require 'app/controllers/TeacherController.php';
Session::start(); Auth::$user=['id'=>1,'role'=>'teacher','full_name'=>'Demo Teacher'];
$assignment=['id'=>1,'course_id'=>1,'course_title'=>'Demo Course','title'=>'Demo Assignment','max_score'=>100,'due_date'=>null,'description'=>'Demo instructions'];
$sub=['id'=>42,'student_id'=>2,'student_name'=>'Demo Student','student_email'=>'student@example.test','submitted_at'=>date('Y-m-d H:i:s'),'status'=>'submitted','score'=>null,'assignment_id'=>1,'assignment_title'=>'Demo Assignment','course_title'=>'Demo Course','max_score'=>100,'content'=>'Demo response','file_path'=>null,'feedback'=>null];
foreach (['assignment','dashboard','grade'] as $view) {
    $data=['assignment'=>$assignment,'submissions'=>[$sub],'sub'=>$sub,'pending'=>[$sub],'courses'=>[], 'stats'=>['courses'=>1,'published'=>1,'students'=>1,'pending'=>1]];
    ob_start(); require 'app/views/teacher/'.$view.'.php'; $html=ob_get_clean();
    if(!preg_match('~(?:href|action)="[^"]*\?r=teacher/([^/"&]+)/42"~',$html,$match)){throw new RuntimeException('No grading route in '.$view);}
    if(!method_exists(TeacherController::class,$match[1]) || !(new ReflectionMethod(TeacherController::class,$match[1]))->isPublic()) {throw new RuntimeException('Non-callable grading route in '.$view.': '.$match[1]);}
}
echo 'grade routes callable';
PHP;
        $sessions = $root . '/tmp/tests/grade-sessions-' . bin2hex(random_bytes(6));
        mkdir($sessions, 0700, true);
        [$exit, $output] = baselineProcess([PHP_BINARY, '-d', 'session.save_path=' . $sessions, '-r', $code], $root);
        if ($exit !== 0 || trim($output) !== 'grade routes callable') {
            throw new RuntimeException('Grading navigation failed: ' . substr($output, 0, 450));
        }
    },
    'SQLite schemas and initial data install without external services' => function (): void {
        $root = dirname(__DIR__);
        $code = 'require "config/config.php"; require "app/helpers/functions.php"; '
            . 'require "database/schema.php"; require "database/schema_v2.php"; require "database/schema_v3.php"; '
            . '$db=new PDO("sqlite::memory:"); $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); '
            . '$db->exec("PRAGMA foreign_keys=ON"); Schema::install($db,"sqlite"); SchemaV2::install($db); SchemaV3::install($db); Schema::seed($db); '
            . 'if($db->query("SELECT count(*) FROM settings")->fetchColumn()<1){throw new RuntimeException("No initial settings");} echo "installed";';
        [$exit, $output] = baselineProcess([PHP_BINARY, '-r', $code], $root);
        if ($exit !== 0 || trim($output) !== 'installed') {
            throw new RuntimeException('SQLite install failed: ' . substr($output, 0, 500));
        }
    },
    'course-management and gradebook links reach their detail actions' => function (): void {
        $root = dirname(__DIR__);
        $code = <<<'PHP'
require 'config/config.php'; require 'app/helpers/functions.php';
$courses=[['id'=>42,'title'=>'Demo Course','description'=>'Demo','level'=>'beginner','status'=>'draft','cover_image'=>null,'lesson_count'=>0,'student_count'=>0,'enrollment_count'=>0]];
$data=['courses'=>$courses, 'categories'=>[]];
require 'app/core/Session.php'; require 'app/core/CSRF.php';
class Database { public static function count(...$args): int { return 0; } }
foreach (['courses'=>'editCourse','gradebook'=>'gradebookCourse'] as $view=>$expected) {
    ob_start(); require 'app/views/teacher/'.$view.'.php'; $html=ob_get_clean();
    if(!str_contains($html,'?r=teacher/'.$expected.'/42')) {throw new RuntimeException('Missing detail action '.$expected.' in '.$view);}
}
echo 'detail routes correct';
PHP;
        [$exit, $output] = baselineProcess([PHP_BINARY, '-r', $code], $root);
        if ($exit !== 0 || trim($output) !== 'detail routes correct') {
            throw new RuntimeException(substr($output, 0, 500));
        }
    },
    'sidebar highlights the current section without its role dashboard' => function (): void {
        $root = dirname(__DIR__);
        $code = <<<'PHP'
require 'config/config.php'; require 'app/helpers/functions.php';
require 'app/core/Auth.php'; require 'app/core/Session.php'; require 'app/core/CSRF.php';
class Database {
    public static function count(...$args): int { return 0; }
    public static function fetchAll(...$args): array { return []; }
    public static function fetch(...$args): ?array { return null; }
}
class Gamification { public static function ensureStreak(...$args): void {} }
foreach ([
    ['teacher','teacher','teacher'],
    ['teacher','teacher/courses','teacher/courses'],
    ['teacher','teacher/editCourse/42','teacher/courses'],
    ['teacher','teacher/gradebook','teacher/gradebook'],
    ['student','student/grades','student/grades'],
    ['admin','admin/users','admin/users'],
] as [$role,$route,$expected]) {
    Auth::$user=['id'=>1,'role'=>$role,'full_name'=>'Demo User','avatar'=>null];
    $data=['currentUri'=>$route,'currentUser'=>Auth::$user];
    ob_start(); require 'app/views/layout/header.php'; $html=ob_get_clean();
    preg_match('~<nav class="sidebar-nav">(.*?)</nav>~s',$html,$nav);
    preg_match_all('~<a href="[^"]*\?r=([^"]+)" class="active"~',$nav[1]??'',$matches);
    if($matches[1]!==[$expected]) {throw new RuntimeException($route.': active routes '.json_encode($matches[1]));}
}
echo 'one correct section';
PHP;
        [$exit, $output] = baselineProcess([PHP_BINARY, '-r', $code], $root);
        if ($exit !== 0 || trim($output) !== 'one correct section') {
            throw new RuntimeException(substr($output, 0, 600));
        }
    },
]];
