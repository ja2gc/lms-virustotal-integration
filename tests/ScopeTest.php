<?php
declare(strict_types=1);
require_once __DIR__ . '/Support/WebFixture.php';
if (!defined('APP_DIR')) define('APP_DIR', dirname(__DIR__) . '/app');
require_once APP_DIR . '/core/Controller.php';
require_once APP_DIR . '/core/Router.php';

function excludedScopeRoutes(): array
{
    return ['quiz/take/1','quiz/submit/1','teacher/quizzes','teacher/createQuiz','teacher/quiz/1',
        'teacher/addQuestion/1','teacher/deleteQuestion/1','flashcard/generateFromCourse/1',
        'flashcards/generateFromCourse/1','ai','gamification','message','messages','forum/course/1',
        'certificate','certificates','poll/course/1','learningpath','learning_paths','whiteboard/course/1',
        'wiki/course/1','group/course/1','code/course/1','analytics','review/search','api/me','oauth/google','push/subscribe'];
}

return ['group'=>'routing', 'tests'=>[
    'empty retained workflows do not advertise quizzes' => function (): void {
        $web=new WebFixture();
        [$code,$output]=$web->php(['-r', "require 'config/config.php'; require 'config/database.php'; Database::query('UPDATE courses SET teacher_id=2 WHERE teacher_id=4'); Database::query('DELETE FROM enrollments WHERE student_id=5');"]);
        if ($code!==0) throw new RuntimeException($output);
        foreach (['other_teacher'=>'teacher/courses','other_student'=>'student/grades'] as $user=>$route) {
            $web->login($user); $page=$web->request($user,$route); WebFixture::pageOk($page,$route);
            if (stripos($page['body'],'quizzes')!==false) throw new RuntimeException('Empty state advertises quizzes: '.$route);
        }
    },
    'historical excluded notification links cannot promote denied actions' => function (): void {
        $web=new WebFixture();
        [$code,$output]=$web->php(['-r', "require 'config/config.php'; require 'config/database.php'; Database::insert('notifications',['user_id'=>3,'title'=>'Historical notice','body'=>'Preserved history','link'=>'quiz/take/1']);"]);
        if ($code!==0) throw new RuntimeException($output);
        $web->login('student');
        if (str_contains($web->request('student','student')['body'],'r=quiz')) throw new RuntimeException('Header promotes excluded quiz.');
        $page=$web->request('student','notifications/markRead/1');
        if (!str_contains($page['location'] ?? '', 'r=notifications')) throw new RuntimeException('Historical notice redirects to excluded action.');
    },
    'assignment submission grading feedback and gradebooks survive cleanup' => function (): void {
        $web=new WebFixture();
        [$code,$output]=$web->php(['-r', <<<'PHP'
require 'config/config.php'; require 'config/database.php';
Database::insert('assignments',['course_id'=>1,'title'=>'Scope Assignment','max_score'=>100,'due_date'=>'2099-12-31 12:00:00']);
Database::insert('grades',['course_id'=>1,'student_id'=>3,'item_type'=>'quiz','item_id'=>1,'item_name'=>'Excluded Quiz Grade','score'=>13,'max_score'=>100]);
PHP]);
        if ($code!==0) throw new RuntimeException($output);
        $web->login('teacher'); $web->login('student');
        $page=$web->request('student','student/submitAssignment/1');
        WebFixture::pageOk($page,'submission form');
        if ($web->request('student','student/submitAssignment/1',['_csrf_token'=>WebFixture::csrf($page),'content'=>'Synthetic cleanup acceptance'])['status']!==302) throw new RuntimeException('Written save failed.');
        $page=$web->request('teacher','teacher/gradeSubmission/1');
        WebFixture::pageOk($page,'teacher review');
        if (!str_contains($page['body'],'Synthetic cleanup acceptance')) throw new RuntimeException('Written content lost.');
        if ($web->request('teacher','teacher/gradeSubmission/1',['_csrf_token'=>WebFixture::csrf($page),'score'=>87,'feedback'=>'Scope feedback retained'])['status']!==302) throw new RuntimeException('Grading failed.');
        foreach (['student/grades/1','student/grades','notifications'] as $route) {
            $page=$web->request('student',$route); WebFixture::pageOk($page,$route);
            if (str_contains($page['body'],'Excluded Quiz Grade')) throw new RuntimeException('Quiz grade leaked.');
        }
        $grades=$web->request('student','student/grades/1')['body'];
        if (!str_contains($grades,'Scope feedback retained') || !str_contains($grades,'87')) throw new RuntimeException('Assignment feedback missing.');
        $page=$web->request('teacher','teacher/gradebookCourse/1'); WebFixture::pageOk($page,'teacher gradebook');
        if (!str_contains($page['body'],'87') || str_contains($page['body'],'<strong>13</strong>')) throw new RuntimeException('Quiz ID collided with assignment grade.');
    },
    'logout and private responses prohibit cache reuse' => function (): void {
        $web = new WebFixture();
        $web->login('student');
        foreach (['student','auth/logout','student'] as $i=>$route) {
            $page=$web->request('student',$route);
            if ($page['status'] !== ($i===0 ? 200 : 302)) throw new RuntimeException('Unexpected logout boundary.');
            if (!preg_grep('~^Cache-Control:.*no-store~i',$page['headers'])) throw new RuntimeException('Private response is cacheable.');
        }
        $worker=file_get_contents(dirname(__DIR__).'/public/sw.js');
        if (str_contains($worker,"addEventListener('fetch'") || !str_contains($worker,'unregister()')) throw new RuntimeException('Offline worker not retired.');
    },
    'excluded actions and aliases are denied before authentication or CSRF' => function (): void {
        $web = new WebFixture();
        foreach (['guest','admin','teacher','student'] as $role) {
            if ($role !== 'guest') $web->login($role);
            foreach (excludedScopeRoutes() as $route) {
                foreach ([$route,strtoupper($route)] as $variant) {
                    foreach ([null,[]] as $post) {
                        if ($web->request($role,$variant,$post)['status'] !== 404) throw new RuntimeException($role.' reached '.$variant);
                    }
                }
            }
        }
    },
    'retained pages contain no excluded links or quiz assessments' => function (): void {
        $web = new WebFixture();
        foreach (['admin','teacher','student'] as $role) {
            $web->login($role);
            foreach ([$role,'course/view/1','calendar','flashcards','auth/profile',...($role==='student'?['student/grades','student/grades/1']:[])] as $route) {
                $page=$web->request($role,$route);
                WebFixture::pageOk($page,$role.' '.$route);
                preg_match_all('~(?:href|action)="([^"]+)"~',$page['body'],$links);
                foreach ($links[1] as $link) {
                    parse_str(parse_url(html_entity_decode($link),PHP_URL_QUERY) ?? '',$query);
                    if (isset($query['r']) && Router::resolve($query['r'])===null) throw new RuntimeException('Excluded/broken link '.$query['r'].' on '.$route);
                }
                if (preg_match('~Quiz attempts|Quizzes Taken|across all quizzes|Quiz bank|AI Assistant|Forum Topics~i',$page['body'])) throw new RuntimeException('Excluded promotion on '.$route);
            }
        }
    },
]];

