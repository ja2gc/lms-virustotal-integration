<?php
declare(strict_types=1);
require_once __DIR__.'/Support/ScanFixture.php';
require_once __DIR__.'/Support/WebFixture.php';

return ['group'=>'persistence','tests'=>[
    'documented CLI migration runs twice on a disposable installed baseline' => function (): void {
        $web=new WebFixture();
        foreach ([1,2] as $run) {
            [$code,$output]=$web->php(['bin/migrate.php']);
            scanCheck($code===0 && str_contains($output,'Scan schema version 1 ready.'),'CLI migration failed on run '.$run);
        }
    },
    'retained account and course deletes preserve existing cascade behavior' => function (): void {
        $f=new ScanFixture(); $f->migrate(); $file=$f->revision();
        $f->repository()->requestRescan(1,1,str_repeat('d',32));
        $f->db->exec('DELETE FROM users WHERE id=1');
        scanCheck(count($f->repository()->getAttemptHistory($file))===2,'Deleting requester removed scan history.');
        $f->db->exec('DELETE FROM users WHERE id=3');
        scanCheck((int)$f->db->query('SELECT COUNT(*) FROM submission_files')->fetchColumn()===0,'Student deletion left file rows.');
        $f=new ScanFixture(); $f->migrate(); $f->revision(); $f->db->exec('DELETE FROM courses WHERE id=1');
        scanCheck((int)$f->db->query('SELECT COUNT(*) FROM scan_attempts')->fetchColumn()===0,'Course deletion left scan rows.');
    },
    'nested registration failure preserves caller transaction and prior pointer' => function (): void {
        $f=new ScanFixture(); $f->migrate(); $file=$f->revision();
        $f->db->exec("CREATE TRIGGER fixture_failure BEFORE INSERT ON scan_attempts BEGIN SELECT RAISE(ABORT,'fixture'); END");
        $f->db->beginTransaction(); $f->db->exec("UPDATE submissions SET content='outer work' WHERE id=1");
        scanThrows(fn()=>$f->revision());
        scanCheck($f->db->inTransaction(),'Repository closed caller transaction.'); $f->db->commit();
        scanCheck($f->repository()->getSubmissionContext(1)['fileId']===$file,'Nested rollback lost old pointer.');
        scanCheck($f->db->query('SELECT content FROM submissions WHERE id=1')->fetchColumn()==='outer work','Nested rollback lost caller work.');
    },
    'legacy attachments are unresolved until Member 1 imports them' => function (): void {
        $f=new ScanFixture(); $f->migrate();
        $f->db->exec("UPDATE submissions SET file_path='submissions/legacy.txt' WHERE id=1");
        $context=$f->repository()->getSubmissionContext(1);
        scanCheck($context['processingState']==='failed' && $context['reason']==='legacy_file_pending_migration','Legacy file incorrectly shown as text-only.');
    },
    'two independent worker processes cannot claim the same attempt' => function (): void {
        $f=new ScanFixture(); $f->migrate(); $f->revision();
        $directory=dirname(__DIR__).'/tmp/tests'; if (!is_dir($directory)) mkdir($directory,0700,true);
        $path=tempnam($directory,'claims-');
        $f->db->exec('VACUUM INTO '.$f->db->quote($path));
        $code= <<<'PHP'
require $argv[1]; $db=new PDO('sqlite:'.$argv[2]); $r=new ScanAttemptRepository($db);
$job=$r->claimDue($argv[3],'2026-11-18T00:00:00Z','2026-11-18T00:01:30Z'); echo $job===null?'none':$job['attemptId'];
PHP;
        $workers=[];
        try {
            foreach (['one','two'] as $token) {
                $process=proc_open([PHP_BINARY,'-r',$code,dirname(__DIR__).'/app/scan/bootstrap.php',$path,$token],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
                if (!is_resource($process)) throw new RuntimeException('Cannot start claim fixture.');
                fclose($pipes[0]); $workers[]=[$process,$pipes];
            }
            $outputs=[];
            foreach ($workers as [$process,$pipes]) {
                $outputs[]=stream_get_contents($pipes[1]); $error=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
                scanCheck(proc_close($process)===0,'Worker fixture failed: '.$error);
            }
            sort($outputs); scanCheck($outputs===['1','none'],'Workers shared a claim.');
        } finally { foreach ([$path,$path.'-wal',$path.'-shm'] as $file) if (is_file($file)) unlink($file); }
    },
    'expired leases cannot publish results even before another worker claims' => function (): void {
        $f=new ScanFixture(); $f->migrate(); $f->revision(); $now='2026-11-18T00:00:00Z';
        $r=new ScanAttemptRepository($f->db,static function()use(&$now):string{return $now;});
        $job=$r->claimDue('expire',$now,'2026-11-18T00:01:30Z'); $now='2026-11-18T00:02:00Z';
        scanCheck(!$r->saveProgress($job['attemptId'],'expire',['reason'=>'late']),'Expired lease saved progress.');
        scanCheck(!$r->saveResult($job['attemptId'],'expire',['status'=>'Unresolved','reason'=>'provider_timeout','retryable'=>false,'retryAfter'=>null],['state'=>'failed','nextRunAt'=>null,'failureCount'=>1,'completedAt'=>$now]),'Expired result published.');
    },
    'Member 1 can compose academic saves and revision registration in one transaction' => function (): void {
        $f=new ScanFixture(); $f->migrate();
        $f->db->beginTransaction();
        $f->db->exec("UPDATE submissions SET content='transaction fixture' WHERE id=1");
        $f->revision();
        $f->db->rollBack();
        scanCheck($f->repository()->getSubmissionContext(1)['fileId']===null,'Outer rollback left file pointer.');
        scanCheck((int)$f->db->query('SELECT COUNT(*) FROM scan_attempts')->fetchColumn()===0,'Outer rollback left queue work.');
    },
    'invalid metadata and result transitions reject without changing stored state' => function (): void {
        $f=new ScanFixture(); $f->migrate(); $r=$f->repository();
        scanThrows(fn()=>$r->registerFileRevision(1,['storageKey'=>'../escape']));
        $f->revision(); $job=$r->claimDue('guard','2026-11-18T00:00:00Z','2026-11-18T00:01:30Z');
        $transition=['state'=>'waiting','nextRunAt'=>'2026-11-18T00:01:00Z','failureCount'=>0,'completedAt'=>null];
        scanThrows(fn()=>$r->saveResult($job['attemptId'],'guard',['status'=>'No Detections Reported','reason'=>'no_detections_reported','retryable'=>true,'retryAfter'=>null],$transition));
        scanCheck($r->getSubmissionContext(1)['processingState']==='running','Invalid write changed state.');
    },
    'overdue polls terminate and old revision results never replace the current projection' => function (): void {
        $f=new ScanFixture(); $f->migrate(); $old=$f->revision(); $r=$f->repository();
        $job=$r->claimDue('old','2026-11-18T00:00:00Z','2026-11-18T00:01:30Z');
        $new=$f->revision();
        $r->saveResult($job['attemptId'],'old',['status'=>'Flagged','reason'=>'malicious_detected','retryable'=>false,'retryAfter'=>null],['state'=>'completed','nextRunAt'=>null,'failureCount'=>0,'completedAt'=>'2026-11-18T00:00:30Z']);
        scanCheck($r->getSubmissionContext(1)['fileId']===$new && $r->getSubmissionContext(1)['advisoryStatus']===null,'Late result replaced current status.');
        $next=$r->claimDue('new','2026-11-18T00:00:00Z','2026-11-18T00:01:30Z');
        $r->saveProgress($next['attemptId'],'new',['analysisId'=>'fixture','pollDeadlineAt'=>'2026-11-18T00:01:00Z']);
        scanCheck($r->claimDue('expired','2026-11-18T00:02:00Z','2026-11-18T00:03:30Z')===null,'Overdue poll requeued.');
        scanCheck($r->getSubmissionContext(1)['reason']==='polling_exhausted','Deadline failure missing.');
    },
    'forward migration repeats without data loss and CLI bootstrap has no session' => function (): void {
        $f=new ScanFixture(); $f->migrate(); $f->migrate();
        scanCheck((int)$f->db->query('SELECT COUNT(*) FROM scan_schema_versions')->fetchColumn()===1,'Duplicate migration.');
        scanCheck((int)$f->db->query('SELECT COUNT(*) FROM submissions')->fetchColumn()===2,'Baseline records lost.');
        scanCheck(session_status()!==PHP_SESSION_ACTIVE,'Bootstrap started a session.');
    },
    'revision registration separates hashes, preserves history and queues once' => function (): void {
        $f=new ScanFixture(); $f->migrate(); $a=$f->revision(); $b=$f->revision(); $c=$f->revision(2);
        $r=$f->repository();
        scanCheck(count(array_unique([$a,$b,$c]))===3,'File identities collapsed.');
        scanCheck($r->getSubmissionContext(1)['fileId']===$b,'Replacement pointer wrong.');
        scanCheck($r->getFileContext($a)['submissionId']===1,'Old revision lost.');
        scanCheck(count($r->getAttemptHistory($a))===1,'Missing automatic attempt.');
        scanThrows(fn()=>$f->db->exec("UPDATE submissions SET current_file_id=$c WHERE id=1"));
        scanThrows(fn()=>$f->db->exec("UPDATE submission_files SET active_scan_attempt_id=3 WHERE id=$a"));
        scanThrows(fn()=>$f->db->exec("INSERT INTO scan_attempts(submission_file_id,origin,idempotency_key,requested_at,updated_at) VALUES($a,'automatic','duplicate','now','now')"));
        scanThrows(fn()=>$f->db->exec("UPDATE submission_files SET sha256='changed' WHERE id=$a"));
    },
    'rescan tokens replay actual state and reject another actor or submission' => function (): void {
        $f=new ScanFixture(); $f->migrate(); $file=$f->revision(); $r=$f->repository();
        $first=$r->requestRescan(1,2,str_repeat('a',32));
        $replay=$r->requestRescan(1,2,str_repeat('a',32));
        scanCheck($first['created'] && !$replay['created'] && $first['attemptId']===$replay['attemptId'],'Replay duplicated work.');
        scanThrows(fn()=>$r->requestRescan(2,2,str_repeat('a',32)));
        scanThrows(fn()=>$r->requestRescan(1,4,str_repeat('a',32)));
        scanThrows(fn()=>$r->requestRescan(1,3,str_repeat('b',32)));
        scanCheck(count($r->getAttemptHistory($file))===2,'History incorrect.');
    },
    'claims and result writes guard leases and retain normalized projections' => function (): void {
        $f=new ScanFixture(); $f->migrate(); $file=$f->revision(); $r=$f->repository();
        $job=$r->claimDue('lease-a','2026-11-18T00:00:00Z','2026-11-18T00:01:30Z');
        scanCheck($job['fileId']===$file && $job['startInFlight']===false,'Claim projection incorrect.');
        scanCheck($r->claimDue('lease-b','2026-11-18T00:00:00Z','2026-11-18T00:01:30Z')===null,'Double claim.');
        $result=['status'=>'Unresolved','reason'=>'inconclusive_engines','retryable'=>false,'retryAfter'=>null];
        $transition=['state'=>'completed','nextRunAt'=>null,'failureCount'=>0,'completedAt'=>'2026-11-18T00:00:30Z'];
        scanCheck(!$r->saveResult($job['attemptId'],'wrong',$result,$transition),'Wrong lease published.');
        scanCheck($r->saveResult($job['attemptId'],'lease-a',$result,$transition),'Owned result rejected.');
        scanCheck(!$r->saveResult($job['attemptId'],'lease-a',$result,$transition),'Terminal result rewritten.');
        scanCheck($r->getSubmissionContext(1)['advisoryStatus']==='Unresolved','Missing status.');
        scanThrows(fn()=>$r->saveProgress($job['attemptId'],'lease-a',['fileId'=>99]));
    },
    'uncertain starts fail after expiry and safe polling resumes with its handle' => function (): void {
        $f=new ScanFixture(); $f->migrate(); $f->revision(); $r=$f->repository();
        $job=$r->claimDue('first','2026-11-18T00:00:00Z','2026-11-18T00:01:30Z');
        $r->saveProgress($job['attemptId'],'first',['startInFlight'=>true,'step'=>'start']);
        scanCheck($r->claimDue('second','2026-11-18T00:02:00Z','2026-11-18T00:03:30Z')===null,'Uncertain POST replayed.');
        scanCheck($r->getSubmissionContext(1)['reason']==='start_outcome_unknown','Uncertain start missing.');
        $f->revision(); $job=$r->claimDue('poll-a','2026-11-18T00:02:00Z','2026-11-18T00:03:30Z');
        $r->saveProgress($job['attemptId'],'poll-a',['analysisId'=>'fixture-handle','step'=>'poll','pollDeadlineAt'=>'2026-11-18T00:32:00Z']);
        $next=$r->claimDue('poll-b','2026-11-18T00:04:00Z','2026-11-18T00:05:30Z');
        scanCheck($next['analysisId']==='fixture-handle','Stored handle lost.');
        scanCheck(!$r->saveProgress($job['attemptId'],'poll-a',['reason'=>'stale']),'Stale lease changed work.');
    },
    'failed revision transaction rolls back rows and current pointers' => function (): void {
        $f=new ScanFixture(); $f->migrate(); $first=$f->revision();
        $f->db->exec("CREATE TRIGGER fixture_failure BEFORE INSERT ON scan_attempts BEGIN SELECT RAISE(ABORT,'fixture'); END");
        scanThrows(fn()=>$f->revision());
        scanCheck($f->repository()->getSubmissionContext(1)['fileId']===$first,'Rollback lost prior file.');
        scanCheck((int)$f->db->query('SELECT COUNT(*) FROM submission_files')->fetchColumn()===1,'Orphan revision.');
    },
]];

