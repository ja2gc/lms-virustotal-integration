<?php
declare(strict_types=1);
require_once __DIR__.'/Support/ScanWebFixture.php';

return ['group'=>'status','tests'=>[
    'status JSON distinguishes queued and no-file records and contains public fields only' => function (): void {
        $web=scanWebFixture(); $web->login('student'); $web->login('other_student');
        $data=scanJson($web->request('student','scan/status/1'),200);
        $keys=array_keys($data); sort($keys); $expected=['contractVersion','submissionId','fileId','attemptId','processingState','advisoryStatus','reason','checkedAt'];sort($expected);
        if ($keys!==$expected || $data['processingState']!=='queued' || $data['advisoryStatus']!==null || !is_int($data['attemptId'])) throw new RuntimeException('Status contract mismatch.');
        $empty=scanJson($web->request('other_student','scan/status/2'),200);
        if ($empty['processingState']!=='not_applicable' || $empty['fileId']!==null || $empty['advisoryStatus']!==null) throw new RuntimeException('Text-only record misclassified.');
    },
    'unknown stored status is conservative and status GET never queues work' => function (): void {
        $web=scanWebFixture();$web->login('student');
        [$code]=$web->php(['-r',"require 'config/config.php';require 'config/database.php'; Database::query('PRAGMA ignore_check_constraints=ON'); Database::query(\"UPDATE scan_attempts SET state='unexpected',advisory_status='Safe' WHERE id=1\");"]);
        if ($code!==0) throw new RuntimeException('Cannot prepare malformed status fixture.');
        $data=scanJson($web->request('student','scan/status/1'),200);
        if ($data['advisoryStatus']!=='Unresolved' || $data['processingState']!=='failed') throw new RuntimeException('Unknown evidence was favorable.');
        [$code,$count]=$web->php(['-r',"require 'config/config.php';require 'config/database.php';echo Database::count('scan_attempts');"]);
        if ($code!==0 || $count!=='1') throw new RuntimeException('GET created scan work.');
    },
]];
