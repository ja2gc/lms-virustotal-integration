<?php
declare(strict_types=1);
require_once __DIR__.'/Support/WebFixture.php';
return ['group'=>'install','tests'=>[
    'fresh installer migration seed and documented worker deliver a private fixture advisory' => function():void {
        $web=new WebFixture(false);
        $installer=$web->request('guest',$web->baseUrl.'/install.php');WebFixture::pageOk($installer,'Fresh installer');
        if(!preg_match('/name="_csrf" value="([a-f0-9]+)"/',$installer['body'],$token))throw new RuntimeException('Installer token absent.');
        $installed=$web->request('guest',$web->baseUrl.'/install.php',['_csrf'=>$token[1],'step'=>'install','admin_user'=>'fresh_admin','admin_email'=>'fresh@example.test','admin_name'=>'Synthetic Administrator','admin_pass'=>'SyntheticOnly!2026','admin_pass2'=>'SyntheticOnly!2026']);
        WebFixture::pageOk($installed,'Installer submit');
        if(!str_contains($installed['body'],'Installation Complete'))throw new RuntimeException('Fresh installation did not finish.');
        foreach([['bin/migrate.php'],['bin/migrate.php'],['bin/seed-demo.php'],['bin/seed-demo.php']] as $args){[$exit]=$web->php($args);if($exit!==0)throw new RuntimeException('Documented migration/seed failed.');}
        $web->login('panel_student','DemoOnly!2026');
        $page=$web->request('panel_student','student/submitAssignment/1');WebFixture::pageOk($page,'Fresh submission form');
        $bytes="Fresh installation synthetic reflection.\n";
        $saved=$web->request('panel_student','student/submitAssignment/1',['_csrf_token'=>WebFixture::csrf($page),'content'=>'Fresh reflection'],['name'=>'fresh.txt','bytes'=>$bytes]);
        if($saved['status']!==302)throw new RuntimeException('Fresh private upload failed.');
        [$exit]=$web->php(['bin/scan-worker.php','--once']);if($exit!==0)throw new RuntimeException('Documented worker start failed.');
        // Advance only the disposable due time; production CLI keeps its ordinary 60-second poll.
        [$exit]=$web->php(['-r',"require 'config/config.php';require 'config/database.php';Database::query(\"UPDATE scan_attempts SET next_run_at='2000-01-01T00:00:00Z' WHERE state='waiting'\");"]);
        if($exit!==0)throw new RuntimeException('Cannot advance fixture due time.');
        [$exit]=$web->php(['bin/scan-worker.php','--once']);if($exit!==0)throw new RuntimeException('Documented worker poll failed.');
        $data=json_decode($web->request('panel_student','scan/status/1')['body'],true,512,JSON_THROW_ON_ERROR);
        if(($data['processingState']??null)!=='completed'||($data['evidenceMode']??null)!=='fixture')throw new RuntimeException('Fresh installation did not complete fixture processing.');
        $download=$web->request('panel_student','submissionFile/download/'.$data['fileId']);
        if($download['status']!==200||$download['body']!==$bytes)throw new RuntimeException('Fresh installation lost uploaded bytes.');
    },
]];
