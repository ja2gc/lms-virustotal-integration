<?php
declare(strict_types=1);
require_once __DIR__.'/Support/ScanFixture.php';
require_once dirname(__DIR__).'/app/scan/bootstrap.php';
foreach (['ScanProviderBudget','ScanProviderClient','FixtureScanTransport','ScanResultProcessor','AutomaticSubmissionResult','TeacherRescanResult','ScanWorker'] as $class) {
    $file=dirname(__DIR__).'/app/scan/'.$class.'.php';
    if (is_file($file)) require_once $file;
}

function workerFixture(): array
{
    $fixture=new ScanFixture(); $fixture->migrate();
    $root=dirname(__DIR__).'/tmp/tests/worker-'.bin2hex(random_bytes(6)); mkdir($root,0700,true);
    $key=bin2hex(random_bytes(16)); file_put_contents($root.'/'.$key,'synthetic');
    $repo=$fixture->repository();
    $file=$repo->registerFileRevision(1,['storageKey'=>$key,'originalName'=>'fixture.txt','sha256'=>hash('sha256','synthetic'),'sizeBytes'=>9,'mimeType'=>'text/plain']);
    return [$fixture,$root,$file];
}

return ['group'=>'worker','tests'=>[
    'automatic and teacher consumers obtain distinct fresh fixture analyses through the same adapter' => function():void {
        [$f,$root,$file]=workerFixture(); $now='2026-11-18T00:00:00Z'; $clock=static function()use(&$now):string{return $now;};
        $requests=[]; $fixtureTransport=new FixtureScanTransport();
        $transport=static function(...$args)use(&$requests,$fixtureTransport):array {$requests[]=$args[0].' '.$args[1];return $fixtureTransport(...$args);};
        $worker=new ScanWorker($f->db,$root,'fixture-key',$transport,$clock,4,500,static fn(int $max):int=>0);
        scanCheck($worker->runOnce(),'Automatic start not claimed');
        $id=$f->db->query('SELECT analysis_id FROM scan_attempts WHERE id=1')->fetchColumn();
        scanCheck(is_string($id)&&str_starts_with($id,'fixture-'),'Fresh handle not persisted');
        $now='2026-11-18T00:01:01Z'; $worker->runOnce();
        scanCheck($f->repository()->getSubmissionContext(1)['advisoryStatus']==='No Detections Reported','Automatic result missing');
        $f->repository()->requestRescan(1,2,str_repeat('c',64));
        $now='2026-11-18T00:02:02Z'; $worker->runOnce();
        $newId=$f->db->query('SELECT analysis_id FROM scan_attempts WHERE id=2')->fetchColumn();
        scanCheck($newId!==$id && is_string($newId),'Rescan reused historical handle');
        $now='2026-11-18T00:03:03Z'; $worker->runOnce();
        scanCheck($f->repository()->getSubmissionContext(1)['advisoryStatus']==='No Detections Reported','Teacher result missing');
        scanCheck(count($requests)===8,'Every start and two-read result must be metered');
        scanCheck(count(json_decode($f->db->query('SELECT request_timestamps_json FROM scan_provider_state')->fetchColumn(),true))===8,'Persisted HTTP count mismatch');
    },
    'stored handles survive worker restart and overdue polls make no provider request' => function():void {
        [$f,$root]=workerFixture(); $now='2026-11-18T00:00:00Z';$clock=static function()use(&$now):string{return $now;};
        (new ScanWorker($f->db,$root,'fixture-key',new FixtureScanTransport(),$clock))->runOnce();
        $now='2026-11-18T00:01:01Z';$requests=0;$fixtureTransport=new FixtureScanTransport();
        $transport=static function(...$args)use(&$requests,$fixtureTransport):array { $requests++;scanCheck($args[0]==='GET','Restart replayed POST');return $fixtureTransport(...$args);};
        (new ScanWorker($f->db,$root,'fixture-key',$transport,$clock))->runOnce();scanCheck($requests===2,'Restart did not poll stored handle');
        $f->repository()->requestRescan(1,2,str_repeat('d',64));$now='2026-11-18T00:02:02Z';
        (new ScanWorker($f->db,$root,'fixture-key',new FixtureScanTransport(),$clock))->runOnce();
        $now='2026-11-18T00:32:03Z';$requests=0;
        (new ScanWorker($f->db,$root,'fixture-key',$transport,$clock))->runOnce();
        scanCheck($requests===0 && $f->repository()->getSubmissionContext(1)['reason']==='polling_exhausted','Expired poll made HTTP request');
    },
    'uncertain POST outcomes never replay after timeout or crashed lease' => function():void {
        [$f,$root]=workerFixture();$now='2026-11-18T00:00:00Z';$clock=static function()use(&$now):string{return $now;};$posts=0;$fixtureTransport=new FixtureScanTransport();
        $transport=static function(...$args)use(&$posts,$fixtureTransport):array {if($args[0]==='POST'){$posts++;return ['httpStatus'=>0,'headers'=>[],'body'=>'','transportError'=>'timeout'];}return $fixtureTransport(...$args);};
        $worker=new ScanWorker($f->db,$root,'fixture-key',$transport,$clock);$worker->runOnce();
        scanCheck($f->repository()->getSubmissionContext(1)['reason']==='start_outcome_unknown','Ambiguous start not terminal');
        $now='2026-11-18T00:02:00Z';$worker->runOnce();scanCheck($posts===1,'Ambiguous POST replayed');
        $f->repository()->requestRescan(1,2,str_repeat('e',64));
        $repo=new ScanAttemptRepository($f->db,$clock);$claim=$repo->claimDue('crash',$now,'2026-11-18T00:03:30Z');
        $repo->saveProgress($claim['attemptId'],'crash',['startInFlight'=>true,'step'=>'start']);
        $now='2026-11-18T00:04:00Z';$worker->runOnce();
        scanCheck($posts===1 && $repo->getSubmissionContext(1)['reason']==='start_outcome_unknown','Crashed start replayed');
    },
    'shared reservations prevent budget overspend and unused result capacity is released' => function():void {
        [$f]=workerFixture();$now='2026-11-18T00:00:00Z';$clock=static function()use(&$now):string{return $now;};
        $first=new ScanProviderBudget($f->db,$clock,2,500);$second=new ScanProviderBudget($f->db,$clock,2,500);$calls=0;
        $metered=$first->metered(static function()use(&$calls):array {$calls++;return ['httpStatus'=>200,'headers'=>[],'body'=>'{}','transportError'=>null];});
        $first->within(2,function()use($second,$metered):void {scanThrows(static fn()=>$second->within(1,static fn()=>null));$metered('GET','https://www.virustotal.com/api/v3/files/a',[],null);});
        $second->within(1,static fn()=>null);
        scanCheck($calls===1,'Reservation counted requests that never started');
        scanCheck(json_decode($f->db->query('SELECT reservations_json FROM scan_provider_state')->fetchColumn(),true)===[],'Unused reservation leaked');
    },
    'quota waits cannot extend poll deadline and authentication pauses until operator correction' => function():void {
        [$f,$root]=workerFixture();$now='2026-11-18T00:00:00Z';$clock=static function()use(&$now):string{return $now;};
        $worker=new ScanWorker($f->db,$root,'fixture-key',new FixtureScanTransport(),$clock,2,2);$worker->runOnce();
        $deadline=$f->db->query('SELECT poll_deadline_at FROM scan_attempts')->fetchColumn();
        $now='2026-11-18T00:01:01Z';$worker->runOnce();
        scanCheck($f->db->query('SELECT poll_deadline_at FROM scan_attempts')->fetchColumn()===$deadline,'Quota wait extended deadline');
        scanCheck($f->repository()->getSubmissionContext(1)['reason']==='polling_exhausted','Quota scheduled beyond deadline');
        $f->repository()->requestRescan(1,2,str_repeat('f',64));$now='2026-11-19T00:00:00Z';
        (new ScanWorker($f->db,$root,'fixture-key',new FixtureScanTransport('auth'),$clock))->runOnce();
        scanCheck($f->repository()->getSubmissionContext(1)['reason']==='provider_auth_failed','Auth error not recorded');
        scanCheck($f->db->query('SELECT paused_until FROM scan_provider_state')->fetchColumn()==='9999-12-31T23:59:59Z','Auth pause missing');
    },
    'retryable reads have bounded backoff and malformed evidence never becomes favorable' => function():void {
        [$f,$root]=workerFixture();$now='2026-11-18T00:00:00Z';$clock=static function()use(&$now):string{return $now;};
        (new ScanWorker($f->db,$root,'fixture-key',new FixtureScanTransport(),$clock))->runOnce();
        $worker=new ScanWorker($f->db,$root,'fixture-key',new FixtureScanTransport('timeout'),$clock,4,500,static fn(int $max):int=>0);
        $now='2026-11-18T00:01:01Z';
        for($i=0;$i<6;$i++){$worker->runOnce();$next=$f->db->query('SELECT next_run_at FROM scan_attempts')->fetchColumn();if($next===false||$next===null)break;$now=$next;}
        $row=$f->db->query('SELECT state,advisory_status,failure_count FROM scan_attempts')->fetch();
        scanCheck($row['state']==='failed'&&$row['advisory_status']==='Unresolved'&&(int)$row['failure_count']===6,'Read retries not bounded');
    },
    'unknown hash uploads validated bytes and malformed start descriptors never replay' => function():void {
        [$f,$root]=workerFixture();$now='2026-11-18T00:00:00Z';$clock=static function()use(&$now):string{return $now;};$fixtureTransport=new FixtureScanTransport();$uploads=0;
        $transport=static function(...$args)use(&$uploads,$fixtureTransport):array {
            if ($args[0]==='GET'&&str_contains($args[1],'/files/')) return ['httpStatus'=>404,'headers'=>[],'body'=>'{}','transportError'=>null];
            if ($args[0]==='POST') { $uploads++;scanCheck(($args[3]['file']??null) instanceof CURLFile,'Upload did not use validated private bytes'); }
            return $fixtureTransport(...$args);
        };
        (new ScanWorker($f->db,$root,'fixture-key',$transport,$clock))->runOnce();
        scanCheck($uploads===1 && is_string($f->db->query('SELECT analysis_id FROM scan_attempts')->fetchColumn()),'Unknown hash upload did not save handle');
        $f->repository()->requestRescan(1,2,str_repeat('a',64));$now='2026-11-18T00:00:01Z';$posts=0;
        $malformed=static function(...$args)use(&$posts,$fixtureTransport):array {if($args[0]==='POST'){$posts++;return ['httpStatus'=>200,'headers'=>[],'body'=>'{"data":null}','transportError'=>null];}return $fixtureTransport(...$args);};
        $worker=new ScanWorker($f->db,$root,'fixture-key',$malformed,$clock);$worker->runOnce();
        scanCheck($f->repository()->getSubmissionContext(1)['reason']==='start_outcome_unknown','Malformed accepted POST descriptor considered replay-safe');
        $now='2026-11-18T00:02:02Z';$worker->runOnce();scanCheck($posts===1,'Malformed start replayed');
    },
    'poll deadline is enforced between analysis and identity requests' => function():void {
        [$f,$root]=workerFixture();$now='2026-11-18T00:00:00Z';$clock=static function()use(&$now):string{return $now;};
        (new ScanWorker($f->db,$root,'fixture-key',new FixtureScanTransport(),$clock))->runOnce();
        $now='2026-11-18T00:29:59Z';$requests=0;$fixtureTransport=new FixtureScanTransport();
        $transport=static function(...$args)use(&$now,&$requests,$fixtureTransport):array {$requests++;$response=$fixtureTransport(...$args);$now='2026-11-18T00:30:01Z';return $response;};
        (new ScanWorker($f->db,$root,'fixture-key',$transport,$clock))->runOnce();
        scanCheck($requests===1&&$f->repository()->getSubmissionContext(1)['reason']==='polling_exhausted','Identity request crossed deadline');
    },
    'unknown quota pauses survive new workers and clearing pause does not erase request counts' => function():void {
        [$f,$root]=workerFixture();$now='2026-11-18T00:00:00Z';$clock=static function()use(&$now):string{return $now;};$requests=0;
        $transport=static function()use(&$requests):array {$requests++;return ['httpStatus'=>429,'headers'=>[],'body'=>'{"error":{"code":"QuotaExceededError"}}','transportError'=>null];};
        (new ScanWorker($f->db,$root,'fixture-key',$transport,$clock))->runOnce();
        scanCheck($f->repository()->getSubmissionContext(1)['processingState']==='waiting','Pre-start quota must remain durable');
        $now='2026-11-18T00:01:01Z';(new ScanWorker($f->db,$root,'fixture-key',$transport,$clock))->runOnce();
        scanCheck($requests===1,'Paused worker attempted HTTP');
        (new ScanProviderBudget($f->db,$clock))->resume();
        scanCheck(count(json_decode($f->db->query('SELECT request_timestamps_json FROM scan_provider_state')->fetchColumn(),true))===1,'Operator resume erased quota history');
    },
    'modified private bytes fail before hash disclosure or scan start' => function():void {
        [$f,$root]=workerFixture();$key=$f->db->query('SELECT storage_key FROM submission_files')->fetchColumn();file_put_contents($root.'/'.$key,'tampered!');
        $calls=0;$transport=static function()use(&$calls):array{$calls++;throw new RuntimeException('Unexpected HTTP');};
        (new ScanWorker($f->db,$root,'fixture-key',$transport,static fn():string=>'2026-11-18T00:00:00Z'))->runOnce();
        scanCheck($calls===0&&$f->repository()->getSubmissionContext(1)['reason']==='file_integrity_failed','Modified bytes sent to provider');
    },
]];
