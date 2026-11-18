<?php
declare(strict_types=1);
require_once __DIR__ . '/Support/WebFixture.php';

return ['group'=>'baseline', 'tests'=>[
    'demo seeding is repeatable, preserves existing users and supplies connected role logins' => function (): void {
        $web = new WebFixture();
        $snapshot = static function () use ($web): string {
            [$exit, $output] = $web->php(['-r', 'require "config/config.php"; require "config/database.php"; $tables=["users","courses","enrollments","modules","lessons","assignments","flashcard_decks","flashcards"]; $counts=[]; foreach($tables as $table){$counts[$table]=Database::count($table);} echo json_encode($counts);']);
            if ($exit !== 0) throw new RuntimeException('Cannot read disposable seed counts.');
            return $output;
        };
        foreach ([1,2] as $run) {
            [$exit, $output] = $web->php(['bin/seed-demo.php']);
            if ($exit !== 0) throw new RuntimeException('Seeder run ' . $run . ' failed: ' . substr($output,0,180));
            $counts = $snapshot();
            if ($run === 1) $first = $counts;
            elseif ($first !== $counts) throw new RuntimeException('Second seed changed row counts.');
        }
        foreach (['panel_admin'=>'admin','panel_teacher'=>'teacher','panel_student'=>'student'] as $user=>$role) {
            $web->login($user, 'DemoOnly!2026');
            WebFixture::pageOk($web->request($user,$role), $role . ' seeded dashboard');
        }
        $web->login('student'); // original unrelated fixture user still works
        WebFixture::pageOk($web->request('panel_student','flashcards'), 'Seeded study data');
        [$exit, $output] = $web->php(['-r', 'require "config/config.php"; require "config/database.php"; $rows=Database::fetchAll("SELECT c.enroll_count,(SELECT count(*) FROM enrollments e WHERE e.course_id=c.id AND e.status=\'active\') AS actual FROM courses c WHERE c.slug LIKE \'panel-demo-%\'"); foreach($rows as $row){if((int)$row["enroll_count"]!==(int)$row["actual"]){exit(1);}}']);
        if ($exit !== 0) throw new RuntimeException('Seeded enrollment count disagrees with its connected rows.');
        $web->php(['-r', 'require "config/config.php"; require "config/database.php"; Database::update("users",["password"=>password_hash("ChangedDemo!2026",PASSWORD_BCRYPT)],"username=:u",["u"=>"panel_admin"]);']);
        [$exit] = $web->php(['bin/seed-demo.php']);
        if ($exit === 0 || $snapshot() !== $first) throw new RuntimeException('Changed demo account was reset or partial seed was committed.');
        $web->login('panel_admin', 'ChangedDemo!2026');
    },
]];
