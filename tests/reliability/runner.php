<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once dirname(__DIR__).'/Support/ScanFixture.php';
require_once dirname(__DIR__,2).'/app/scan/bootstrap.php';

/** Fake-clock recovery experiment; never opens configuration or a provider connection. */
function reliabilityTrial(string $fault,int $trial): array
{
    $wallStart=hrtime(true);
    $fixture=new ScanFixture(); $fixture->migrate();
    $root=dirname(__DIR__,2).'/tmp/tests/reliability-'.bin2hex(random_bytes(8));
    if (!mkdir($root,0700,true)) throw new RuntimeException('Cannot prepare disposable private files.');
    $bytes="Synthetic reliability reflection.\n";
    $source=$root.'/source.txt'; file_put_contents($source,$bytes);
    $now='2026-11-19T00:00:00Z';
    $clock=static function()use(&$now):string{return $now;};
    $service=new ScanSubmissionService($fixture->db,$root.'/private',$clock);
    $submissionId=$service->saveAcademicSubmission(1,3,'Synthetic reliability record',[
        'tmpPath'=>$source,'originalName'=>'reflection.txt','sizeBytes'=>strlen($bytes),'mimeType'=>'text/plain']);
    $repository=new ScanAttemptRepository($fixture->db,$clock);
    $file=$fixture->db->query('SELECT * FROM submission_files')->fetch();
    $baseline=['submissions'=>(int)$fixture->db->query('SELECT count(*) FROM submissions')->fetchColumn(),
        'fileRevisions'=>(int)$fixture->db->query('SELECT count(*) FROM submission_files')->fetchColumn()];
    $phase='start'; $requests=[]; $events=[]; $good=new FixtureScanTransport();
    $transport=static function(string $method,string $url,array $headers,?array $multipart)use(&$phase,&$requests,&$now,$fault,$good):array {
        $analysis=$method==='GET' && str_contains($url,'/analyses/') && !str_ends_with($url,'/item');
        if ($phase==='fault' && $analysis) {
            $response=match($fault) {
                'timeout'=>['httpStatus'=>0,'headers'=>[],'body'=>'','transportError'=>'timeout'],
                '503'=>['httpStatus'=>503,'headers'=>[],'body'=>'{}','transportError'=>null],
                'malformed'=>['httpStatus'=>200,'headers'=>[],'body'=>'{"data":','transportError'=>null],
            };
        } else $response=$good($method,$url,$headers,$multipart);
        $operation=$method==='POST'?'start':(str_ends_with($url,'/item')?'identity':($analysis?'analysis':'lookup'));
        $requests[]=['fakeUtc'=>$now,'phase'=>$phase,'operation'=>$operation,'httpStatus'=>$response['httpStatus'],'transportError'=>$response['transportError']];
        return $response;
    };
    $worker=new ScanWorker($fixture->db,$root.'/private','synthetic-reliability-key',$transport,$clock,4,500,static fn(int $max):int=>0);
    $observe=static function(string $event)use(&$events,&$now,$repository,$submissionId):array {
        $context=$repository->getSubmissionContext($submissionId);
        $events[]=['event'=>$event,'fakeUtc'=>$now,'attemptId'=>$context['attemptId'],
            'processingState'=>$context['processingState'],'advisoryStatus'=>$context['advisoryStatus'],'reason'=>$context['reason']];
        return $context;
    };
    scanCheck($worker->runOnce(),'Initial analysis start was not claimed.'); $observe('fresh_analysis_saved');
    $originalAnalysis=$fixture->db->query('SELECT analysis_id FROM scan_attempts WHERE id=1')->fetchColumn();
    $phase='fault'; $now='2026-11-19T00:01:01Z'; $faultAt=$now;
    scanCheck($worker->runOnce(),'Fault poll was not claimed.');
    $failed=$observe('fault_persisted');
    $reason=match($fault){'timeout'=>'provider_timeout','503'=>'provider_unavailable','malformed'=>'malformed_response'};
    scanCheck($failed['advisoryStatus']==='Unresolved' && $failed['reason']===$reason,'Fault normalization differs from expected policy.');
    $afterFault=$fixture->db->query('SELECT state,next_run_at,failure_count FROM scan_attempts WHERE id=1')->fetch();
    $phase='recovery';
    if ($fault==='malformed') {
        scanCheck($afterFault['state']==='failed' && $afterFault['next_run_at']===null,'Malformed evidence should be terminal, not automatically retried.');
        $now='2026-11-19T00:02:02Z';
        $triggerAt=$now;
        $repository->requestRescan($submissionId,2,str_repeat(dechex($trial),64));
        $observe('authorized_teacher_requested_new_attempt');
        scanCheck($worker->runOnce(),'Explicit recovery attempt was not started.');
        $now='2026-11-19T00:03:03Z';
        scanCheck($worker->runOnce(),'Explicit recovery result was not polled.');
        $trigger='Operator restores valid fixture responses; authorized teacher creates a new rescan attempt. Original malformed attempt remains failed/Unresolved.';
    } else {
        scanCheck($afterFault['state']==='waiting' && $afterFault['failure_count']===1 && is_string($afterFault['next_run_at']),'Transient read was not scheduled for retry.');
        $now=$afterFault['next_run_at']; $triggerAt=$now;
        scanCheck($worker->runOnce(),'Scheduled recovery retry was not claimed.');
        $trigger='Operator restores valid fixture responses before the scheduled read retry; same durable analysis ID is resumed.';
    }
    $recovered=$observe('valid_fixture_evidence_persisted');
    scanCheck($recovered['processingState']==='completed' && $recovered['advisoryStatus']==='No Detections Reported','Recovery did not persist validated fixture evidence.');
    $counts=['submissions'=>(int)$fixture->db->query('SELECT count(*) FROM submissions')->fetchColumn(),
        'fileRevisions'=>(int)$fixture->db->query('SELECT count(*) FROM submission_files')->fetchColumn()];
    scanCheck($baseline===$counts,'Saved academic/file counts changed during provider failure.');
    scanCheck(file_get_contents($root.'/private/'.$file['storage_key'])===$bytes && hash_file('sha256',$root.'/private/'.$file['storage_key'])===$file['sha256'],'Saved bytes were lost or changed.');
    scanCheck($recovered['fileId']===(int)$file['id'],'Recovery attached to a different revision.');
    $history=$repository->getAttemptHistory((int)$file['id']);
    scanCheck(count($history)===($fault==='malformed'?2:1),'Unexpected recovery attempt count.');
    $currentAnalysis=$fixture->db->query('SELECT analysis_id FROM scan_attempts ORDER BY id DESC LIMIT 1')->fetchColumn();
    if ($fault==='malformed') {
        scanCheck($currentAnalysis!==$originalAnalysis && $history[1]['processingState']==='failed' && $history[1]['reason']==='malformed_response','Explicit recovery erased prior failure or reused its analysis.');
    } else {
        scanCheck($currentAnalysis===$originalAnalysis,'Read retry replaced the durable analysis ID.');
        scanCheck(count(array_filter($requests,static fn(array $request):bool=>$request['operation']==='start'))===1,'Recovery replayed a scan-start POST.');
    }
    $result=['fault'=>$fault,'trial'=>$trial,'status'=>'Pass','clock'=>'injected UTC; zero jitter; no real waiting',
        'faultAt'=>$faultAt,'recoveryTriggerAt'=>$triggerAt,'recoveredAt'=>$now,
        'recoveryFakeSeconds'=>strtotime($now)-strtotime($faultAt),
        'wallClockSeconds'=>round((hrtime(true)-$wallStart)/1e9,6),
        'recoveryTrigger'=>$trigger,'firstRetryAt'=>$afterFault['next_run_at'],
        'faultFailureCount'=>(int)$afterFault['failure_count'],'savedBefore'=>$baseline,'savedAfter'=>$counts,
        'byteAndHashCheck'=>'Pass','attemptCount'=>count($history),'requestCount'=>count($requests),
        'requests'=>$requests,'events'=>$events];
    return $result;
}

try {
    if (array_diff(array_slice($argv,1),['--write'])) throw new InvalidArgumentException('Usage: php tests/reliability/runner.php [--write]');
    $trials=[];
    foreach(['timeout','503','malformed'] as $fault) foreach([1,2,3] as $trial) $trials[]=reliabilityTrial($fault,$trial);
    $summary=['experiment'=>'P6 synthetic provider-read recovery','executedAtUtc'=>gmdate('Y-m-d\TH:i:s\Z'),
        'command'=>'php tests/reliability/runner.php --write','source'=>'working tree; final verified revision recorded in team completion manifest',
        'phpVersion'=>PHP_VERSION,'osFamily'=>PHP_OS_FAMILY,'database'=>'SQLite in memory','mode'=>'offline fixtures; actual ScanWorker, repository and VirusTotalAdapter',
        'providerRequests'=>0,'simulatedHttpStarts'=>array_sum(array_column($trials,'requestCount')),
        'limits'=>['Fake-clock durations measure configured scheduling, not provider latency or wall-clock recovery guarantees.',
            'Each trial uses a separate disposable in-memory SQLite database and synthetic private files.',
            'Malformed evidence recovers only through an explicit new teacher attempt; no self-repair claim.',
            'No real key, environment file, live provider connection or user database was used.'],
        'trials'=>$trials,'passed'=>count($trials),'failed'=>0];
    $json=json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
    if (in_array('--write',$argv,true)) {
        $directory=dirname(__DIR__,2).'/evidence/reliability';
        if (!is_dir($directory)) mkdir($directory,0755,true);
        file_put_contents($directory.'/summary.json',$json);
        echo count($trials)." synthetic recovery trials passed; evidence/reliability/summary.json written.\n";
    } else echo $json;
} catch (Throwable $error) { fwrite(STDERR,'Reliability experiment failed: '.$error->getMessage().PHP_EOL);exit(1); }
