<?php
/** Offline fictional localhost data. Never resets or deletes existing records. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
if (ENVIRONMENT !== 'development' || DB_TYPE !== 'sqlite' || !IS_INSTALLED) {
    fwrite(STDERR, "Use an installed development SQLite database. Complete the local installer first.\n");
    exit(1);
}

$db = Database::getInstance();
$db->beginTransaction();
try {
    $users = [];
    foreach (['panel_admin'=>'admin','panel_teacher'=>'teacher','panel_student'=>'student',
        'panel_teacher_b'=>'teacher','panel_student_b'=>'student'] as $username=>$role) {
        $email = $username . '@example.test';
        $existing = Database::fetch('SELECT * FROM users WHERE username = :name OR email = :email', ['name'=>$username,'email'=>$email]);
        if ($existing) {
            if ($existing['username'] !== $username || $existing['email'] !== $email || $existing['role'] !== $role
                || $existing['status'] !== 'active' || !password_verify('DemoOnly!2026', $existing['password'])) {
                throw new RuntimeException('Demo identity collision or changed account: ' . $username . '. Nothing was reset.');
            }
            $users[$username] = (int) $existing['id'];
        } else {
            $users[$username] = Database::insert('users', ['username'=>$username,'email'=>$email,
                'password'=>password_hash('DemoOnly!2026', PASSWORD_BCRYPT), 'full_name'=>'Panel Demo ' . ucwords(str_replace('panel_', '', str_replace('_b',' B',$username))),
                'role'=>$role,'status'=>'active']);
        }
    }
    $courses = [];
    foreach (['a'=>'panel_teacher','b'=>'panel_teacher_b'] as $key=>$teacher) {
        $slug = 'panel-demo-integration-' . $key;
        $course = Database::fetch('SELECT * FROM courses WHERE slug = :slug', ['slug'=>$slug]);
        if ($course && (int) $course['teacher_id'] !== $users[$teacher]) throw new RuntimeException('Demo course identity collision. Nothing was reset.');
        $courses[$key] = $course ? (int) $course['id'] : Database::insert('courses', [
            'teacher_id'=>$users[$teacher], 'title'=>'Systems Integration Lab ' . strtoupper($key), 'slug'=>$slug,
            'description'=>'Fictional localhost demonstration course.', 'status'=>'published', 'visibility'=>'public']);
    }
    foreach (['a'=>'panel_student','b'=>'panel_student_b'] as $key=>$student) {
        if (!Database::fetch('SELECT id FROM enrollments WHERE course_id = :c AND student_id = :s', ['c'=>$courses[$key],'s'=>$users[$student]])) {
            Database::insert('enrollments',['course_id'=>$courses[$key],'student_id'=>$users[$student],'status'=>'active']);
        }
        Database::update('courses', ['enroll_count'=>Database::count('enrollments', "course_id = :c AND status = 'active'", ['c'=>$courses[$key]])], 'id = :c', ['c'=>$courses[$key]]);
    }
    $module = Database::fetch('SELECT id FROM modules WHERE course_id = :c AND title = :t', ['c'=>$courses['a'],'t'=>'Integration Basics']);
    $moduleId = $module ? (int) $module['id'] : Database::insert('modules',['course_id'=>$courses['a'],'title'=>'Integration Basics','sort_order'=>1]);
    if (!Database::fetch('SELECT id FROM lessons WHERE module_id = :m AND title = :t', ['m'=>$moduleId,'t'=>'APIs and file advisories'])) {
        Database::insert('lessons',['module_id'=>$moduleId,'title'=>'APIs and file advisories','content_type'=>'text',
            'content'=>'An API is a defined interface between software systems. Missing security evidence does not mean a file is safe.', 'sort_order'=>1]);
    }
    foreach ($courses as $courseId) {
        if (!Database::fetch('SELECT id FROM assignments WHERE course_id = :c AND title = :t', ['c'=>$courseId,'t'=>'Integration Reflection'])) {
            Database::insert('assignments',['course_id'=>$courseId,'title'=>'Integration Reflection','description'=>'Explain how an LMS can integrate a file-security advisory. Use fictional content only.',
                'max_score'=>100,'due_date'=>date('Y-m-d 23:59:00', strtotime('+3 days'))]);
        }
    }
    $deck = Database::fetch('SELECT id FROM flashcard_decks WHERE user_id = :u AND title = :t', ['u'=>$users['panel_teacher'],'t'=>'Panel Demo Integration Cards']);
    if (!$deck) {
        $deckId = Database::insert('flashcard_decks',['user_id'=>$users['panel_teacher'],'course_id'=>$courses['a'],
            'title'=>'Panel Demo Integration Cards','description'=>'Two manual study cards for a short demonstration.']);
        foreach ([['What is an API?','A defined interface between software systems.'],
            ['Does a failed scan mean a file is safe?','No. Missing or failed evidence remains unresolved.']] as $order=>[$front,$back]) {
            Database::insert('flashcards',['deck_id'=>$deckId,'front'=>$front,'back'=>$back,'sort_order'=>$order]);
        }
    }
    $db->commit();
    echo "Fictional demo data ready. Existing records were preserved.\n";
    echo "Logins: panel_admin, panel_teacher, panel_student (plus panel_teacher_b / panel_student_b).\n";
    echo "Use the localhost-only demo password documented in README.md. No scans, grades, reviews or notifications were fabricated.\n";
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
