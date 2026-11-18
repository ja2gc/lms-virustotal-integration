<?php
declare(strict_types=1);
require_once __DIR__.'/Support/ScanWebFixture.php';

return ['group'=>'access','tests'=>[
    'status and history enforce guest owner teacher course and admin boundaries' => function (): void {
        $web=scanWebFixture(); scanJson($web->request('guest','scan/status/1'),401);
        foreach (['student','teacher','admin','other_student','other_teacher'] as $user) $web->login($user);
        foreach (['student','teacher','admin'] as $user) scanJson($web->request($user,'scan/status/1'),200);
        foreach (['other_student','other_teacher'] as $user) scanJson($web->request($user,'scan/status/1'),403);
        scanJson($web->request('teacher','scan/status/9999'),404);
        scanJson($web->request('student','scan/history/1'),403);
        foreach (['teacher','admin'] as $user) {
            $history=scanJson($web->request($user,'scan/history/1'),200);
            if (count($history['history'])!==1) throw new RuntimeException('Expected automatic history.');
        }
    },
    'rescan uses authorized CSRF POST and server token with idempotent replay' => function (): void {
        $web=scanWebFixture(); foreach (['student','teacher','other_teacher'] as $user) $web->login($user);
        scanJson($web->request('teacher','scan/rescan/1'),405);
        scanJson($web->request('teacher','scan/rescan/1',[]),419);
        scanJson($web->request('teacher','scan/rescan/1',['_csrf_token'=>['invalid']]),419);
        $csrf=WebFixture::csrf($web->request('teacher','teacher'));
        scanJson($web->request('teacher','scan/rescan/1',['_csrf_token'=>$csrf,'requestToken'=>str_repeat('b',64)]),422);
        $status=scanJson($web->request('teacher','scan/status/1'),200);
        $post=['_csrf_token'=>$csrf,'requestToken'=>$status['rescanToken']];
        $first=scanJson($web->request('teacher','scan/rescan/1',$post),202);
        $again=scanJson($web->request('teacher','scan/rescan/1',$post),200);
        if (!$first['created'] || $again['created'] || $first['attemptId']!==$again['attemptId']) throw new RuntimeException('Replay created duplicate.');
        $fresh=scanJson($web->request('teacher','scan/status/1'),200);
        if ($fresh['rescanToken']===$status['rescanToken']) throw new RuntimeException('Fresh rescan token not issued.');
        scanJson($web->request('teacher','scan/rescan/1',$post),200);
        $post['_csrf_token']=WebFixture::csrf($web->request('student','student'));
        scanJson($web->request('student','scan/rescan/1',$post),403);
        $post['_csrf_token']=WebFixture::csrf($web->request('other_teacher','teacher'));
        scanJson($web->request('other_teacher','scan/rescan/1',$post),403);
    },
    'private attachments stream only to owner course teacher admin and never static URLs' => function (): void {
        $web=scanWebFixture(); scanJson($web->request('guest','submissionFile/download/1'),401);
        foreach (['student','teacher','admin','other_student','other_teacher'] as $user) $web->login($user);
        foreach (['student','teacher','admin'] as $user) {
            $page=$web->request($user,'submissionFile/download/1');
            if ($page['status']!==200 || $page['body']!=='synthetic attachment' || !preg_grep('~^Content-Disposition: attachment~i',$page['headers'])) throw new RuntimeException('Authorized attachment failed.');
        }
        foreach (['other_student','other_teacher'] as $user) scanJson($web->request($user,'submissionFile/download/1'),403);
        scanJson($web->request('student','submissionFile/download/9999'),404);
        foreach (['/private/submissions/'.str_repeat('a',32),'/public/uploads/submissions/legacy.txt','/public/uploads/SUBMIS~1/legacy.txt','/public//uploads/submissions/legacy.txt','/public/%75ploads/submissions/legacy.txt','/public/uploads/submissions/../submissions/legacy.txt'] as $path) {
            if ($web->request('guest',$web->baseUrl.$path)['status']!==404) throw new RuntimeException('Static private-file bypass: '.$path);
        }
        $web->php(['-r',"file_put_contents('private/submissions/'.str_repeat('a',32),'short');"]);
        scanJson($web->request('student','submissionFile/download/1'),404);
        $web->php(['-r',"unlink('private/submissions/'.str_repeat('a',32));"]);
        scanJson($web->request('student','submissionFile/download/1'),404);
    },
]];
