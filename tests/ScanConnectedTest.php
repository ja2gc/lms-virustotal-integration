<?php
declare(strict_types=1);
require_once __DIR__.'/Support/WebFixture.php';
return ['group'=>'connected','tests'=>[
    'real multipart upload private download and rejected replacement keep exact file identity' => function(): void {
        $web=new WebFixture();
        [$code]=$web->php(['bin/migrate.php']);
        if ($code!==0) throw new RuntimeException('Fixture scan migration failed.');
        [$code]=$web->php(['-r',"require 'config/config.php';require 'config/database.php';Database::insert('assignments',['course_id'=>1,'title'=>'Connected synthetic assignment']);"]);
        if ($code!==0) throw new RuntimeException('Fixture assignment setup failed.');
        $web->login('student');
        $form=$web->request('student','student/submitAssignment/1');
        $bytes="Synthetic assignment reflection.\n";
        $saved=$web->request('student','student/submitAssignment/1',['_csrf_token'=>WebFixture::csrf($form),'content'=>'Synthetic reflection'],['name'=>'reflection.txt','bytes'=>$bytes]);
        if ($saved['status']!==302) throw new RuntimeException('Multipart save rejected.');
        $status=json_decode($web->request('student','scan/status/1')['body'],true,512,JSON_THROW_ON_ERROR);
        if (!is_int($status['fileId']??null) || $status['processingState']!=='queued') throw new RuntimeException('Upload did not create durable private revision.');
        $download=$web->request('student','submissionFile/download/'.$status['fileId']);
        if ($download['status']!==200 || $download['body']!==$bytes) throw new RuntimeException('Private bytes differ from upload.');
        $form=$web->request('student','student/submitAssignment/1');
        $rejected=$web->request('student','student/submitAssignment/1',['_csrf_token'=>WebFixture::csrf($form),'content'=>'Preserve this correction'],['name'=>'blocked.php','bytes'=>'<?php echo 1;']);
        if ($rejected['status']!==422 || !str_contains($rejected['body'],'Preserve this correction')) throw new RuntimeException('Invalid replacement lost form input or reported success.');
        $after=json_decode($web->request('student','scan/status/1')['body'],true,512,JSON_THROW_ON_ERROR);
        if ($after['fileId']!==$status['fileId']) throw new RuntimeException('Rejected replacement changed current attachment.');
        $foreign=$web->request('other_student','submissionFile/download/'.$status['fileId']);
        if ($foreign['status']===200) throw new RuntimeException('Guest/foreign download accepted.');
        $process= <<<'PHP'
require 'config/config.php'; require 'config/database.php'; require 'app/scan/bootstrap.php';
$now=gmdate('Y-m-d\TH:i:s\Z',time()+180);
$clock=static function()use(&$now):string{return $now;};
$worker=new ScanWorker(Database::getInstance(),getenv('SCAN_PRIVATE_DIR'),'fixture-key',new FixtureScanTransport(),$clock);
$worker->runOnce();
$now=gmdate('Y-m-d\TH:i:s\Z',time()+241);
$worker->runOnce();
PHP;
        [$code]=$web->php(['-r',$process]);
        if ($code!==0) throw new RuntimeException('Connected fixture worker failed.');
        $completed=json_decode($web->request('student','scan/status/1')['body'],true,512,JSON_THROW_ON_ERROR);
        if ($completed['processingState']!=='completed' || $completed['advisoryStatus']!=='No Detections Reported') throw new RuntimeException('Uploaded file never obtained fixture result.');
        if (($completed['evidenceMode']??null)!=='fixture') throw new RuntimeException('Offline advisory is not labeled as fixture evidence.');
        $web->login('teacher');
        $teacher=json_decode($web->request('teacher','scan/status/1')['body'],true,512,JSON_THROW_ON_ERROR);
        $review=$web->request('teacher','teacher/assignment/1');
        WebFixture::pageOk($review,'Teacher scan review');
        $request=['_csrf_token'=>WebFixture::csrf($review),'requestToken'=>$teacher['rescanToken']];
        $rescan=$web->request('teacher','scan/rescan/1',$request);
        if ($rescan['status']!==202) throw new RuntimeException('Teacher rescan did not queue.');
        $replay=$web->request('teacher','scan/rescan/1',$request);
        if ($replay['status']!==200 || json_decode($replay['body'],true)['attemptId']!==json_decode($rescan['body'],true)['attemptId']) throw new RuntimeException('Rescan replay created more work.');
        [$code]=$web->php(['-r',str_replace(['time()+180','time()+241'],['time()+360','time()+421'],$process)]);
        if ($code!==0) throw new RuntimeException('Connected fixture rescan worker failed.');
        $history=json_decode($web->request('teacher','scan/history/1')['body'],true,512,JSON_THROW_ON_ERROR);
        if (count($history['history'])!==2 || $history['history'][0]['processingState']!=='completed') throw new RuntimeException('Fresh teacher result/history missing.');
        [$code,$same]=$web->php(['-r',"require 'config/config.php';require 'config/database.php';echo Database::getInstance()->query('SELECT COUNT(DISTINCT analysis_id) FROM scan_attempts')->fetchColumn();"]);
        if ($code!==0 || trim($same)!=='2') throw new RuntimeException('Teacher rescan reused analysis identity.');
    },
]];
