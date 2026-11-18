<?php
declare(strict_types=1);
require_once __DIR__ . '/Support/WebFixture.php';

return ['group' => 'flashcards', 'tests' => [
    'manual deck creation, study and review persist through real HTTP' => function (): void {
        $web = new WebFixture();
        $web->login('student');
        $form = $web->request('student', 'flashcards/create');
        WebFixture::pageOk($form, 'Create deck');
        $result = $web->request('student', 'flashcards/create', ['_csrf_token'=>WebFixture::csrf($form),
            'title'=>'Manual HTTP Trial','course_id'=>1,'front'=>['What is an API?', ''], 'back'=>['A defined interface between software systems.', '']]);
        if ($result['status'] !== 302 || !preg_match('~flashcards/view/(\d+)~', $result['location'] ?? '', $deck)) throw new RuntimeException('Deck was not created.');
        $view = $web->request('student', 'flashcards/view/' . $deck[1]);
        WebFixture::pageOk($view, 'Created deck');
        if (!str_contains($view['body'], 'What is an API?')) throw new RuntimeException('Saved card missing.');
        $study = $web->request('student', 'flashcards/study/' . $deck[1]);
        WebFixture::pageOk($study, 'Study session');
        if (!preg_match('/const cards = (.*);/', $study['body'], $cards)) throw new RuntimeException('Study data missing.');
        $cards = json_decode($cards[1], true, 512, JSON_THROW_ON_ERROR);
        $review = $web->request('student', 'flashcard/review', ['_csrf_token'=>WebFixture::csrf($study),'review_id'=>$cards[0]['review_id'],'rating'=>3]);
        $response = json_decode($review['body'], true, 512, JSON_THROW_ON_ERROR);
        if ($review['status'] !== 200 || ($response['ok'] ?? false) !== true || ($response['interval'] ?? 0) !== 1) throw new RuntimeException('Review did not persist its next interval.');
        $again = $web->request('student', 'flashcards/study/' . $deck[1]);
        if ($again['status'] !== 302 || !str_contains($again['location'] ?? '', 'flashcards/view/')) throw new RuntimeException('Reviewed card is still due.');
    },
    'private deck view and study reject a different student and teacher' => function (): void {
        $web = new WebFixture();
        foreach (['student', 'other_teacher'] as $role) {
            $web->login($role);
            foreach (['view','study'] as $action) {
                if ($web->request($role, 'flashcards/' . $action . '/1')['status'] !== 403) throw new RuntimeException($role . ' read another user private deck via ' . $action);
            }
        }
        foreach (['teacher','admin'] as $role) {
            $web->login($role);
            WebFixture::pageOk($web->request($role, 'flashcards/view/1'), $role . ' permitted deck');
        }
        WebFixture::pageOk($web->request('student', 'flashcards/view/2'), 'Enrolled course deck');
        WebFixture::pageOk($web->request('student', 'flashcards/view/3'), 'Public deck');
        $study = $web->request('student', 'flashcards/study/2');
        preg_match('/const cards = (.*);/', $study['body'], $cards);
        $cards = json_decode($cards[1], true, 512, JSON_THROW_ON_ERROR);
        $web->php(['-r','require "config/config.php"; require "config/database.php"; Database::update("enrollments",["status"=>"dropped"],"student_id=:s AND course_id=:c",["s"=>3,"c"=>1]);']);
        if ($web->request('student','flashcard/review',['_csrf_token'=>WebFixture::csrf($study),'review_id'=>$cards[0]['review_id'],'rating'=>3])['status'] !== 403) throw new RuntimeException('Revoked enrollment still permits course review.');
    },
    'deck creation validates content and course scope; deletion requires CSRF POST' => function (): void {
        $web = new WebFixture();
        $web->login('student');
        $form = $web->request('student', 'flashcards/create');
        $token = WebFixture::csrf($form);
        if ($web->request('student','flashcards/create',['_csrf_token'=>$token,'title'=>'','front'=>[''],'back'=>['']])['status'] !== 422) throw new RuntimeException('Empty deck accepted.');
        $invalid = $web->request('student','flashcards/create',['_csrf_token'=>$token,'title'=>'Keep this deck',
            'front'=>['Complete question','Unfinished question'], 'back'=>['Keep this answer','']]);
        if ($invalid['status'] !== 422 || !str_contains($invalid['body'],'value="Keep this deck"') || !str_contains($invalid['body'],'value="Complete question"') || !str_contains($invalid['body'],'value="Keep this answer"')) throw new RuntimeException('Validation discarded entered deck/card content.');
        if ($web->request('student','flashcards/create',['_csrf_token'=>$token,'title'=>'Wrong course','course_id'=>2,'front'=>['Q'],'back'=>['A']])['status'] !== 403) throw new RuntimeException('Unenrolled course deck accepted.');
        $web->login('teacher');
        $view = $web->request('teacher', 'flashcards/view/1');
        if ($web->request('teacher','flashcards/delete/1')['status'] !== 405) throw new RuntimeException('GET can delete a deck.');
        if ($web->request('teacher','flashcards/delete/1',[])['status'] !== 419) throw new RuntimeException('Delete omitted CSRF protection.');
        if ($web->request('teacher','flashcards/delete/1',['_csrf_token'=>WebFixture::csrf($view)])['status'] !== 302) throw new RuntimeException('Owner cannot delete via POST.');
        if ($web->request('teacher','flashcards/view/1')['status'] !== 404) throw new RuntimeException('Deleted deck remains readable.');
    },
]];
