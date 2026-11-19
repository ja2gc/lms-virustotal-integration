<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require_once dirname(__DIR__).'/Support/WebFixture.php';

// Run from the repository root: php tests/load/submissions.php [--smoke]
// No provider runs. All generated DBs/files/logs remain in ignored tmp/.
try {
    $smoke=in_array('--smoke',array_slice($argv,1),true);
    foreach(array_slice($argv,1) as $argument) if($argument!=='--smoke') throw new InvalidArgumentException('Unknown load option.');
    $repository=dirname(__DIR__,2);
    $output=$repository.'/tmp/load/'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));mkdir($output,0700,true);
    $fixture=new WebFixture();
    [$code]=$fixture->php(['bin/migrate.php']);if($code!==0)throw new RuntimeException('Disposable migration failed.');
    $password=bin2hex(random_bytes(18));
    $seed= <<<'PHP'
require 'config/config.php';require 'config/database.php';
$hash=password_hash($argv[1],PASSWORD_BCRYPT);
for($i=1;$i<=20;$i++){
    $name=sprintf('load_student_%02d',$i);
    $id=Database::insert('users',['username'=>$name,'email'=>$name.'@example.test','password'=>$hash,'full_name'=>'Synthetic Load Student','role'=>'student','status'=>'active']);
    Database::insert('enrollments',['course_id'=>1,'student_id'=>$id,'status'=>'active']);
}
echo Database::insert('assignments',['course_id'=>1,'title'=>'Synthetic load reflection']);
PHP;
    [$code,$assignment]=$fixture->php(['-r',$seed,$password]);if($code!==0||!ctype_digit(trim($assignment)))throw new RuntimeException('Disposable load data failed.');
    $snapshot= <<<'PHP'
require 'config/config.php';require 'config/database.php';
$db=Database::getInstance();$counts=[];
foreach(['submissions','submission_files','scan_attempts'] as $table)$counts[$table]=(int)$db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
$counts['queued']=(int)$db->query("SELECT COUNT(*) FROM scan_attempts WHERE state='queued'")->fetchColumn();
$oldest=$db->query("SELECT MIN(requested_at) FROM scan_attempts WHERE state='queued'")->fetchColumn();
$counts['oldestQueuedSeconds']=$oldest?max(0,time()-strtotime($oldest)):0;
$counts['dbBytes']=filesize(DB_PATH);
echo json_encode($counts,JSON_THROW_ON_ERROR);
PHP;
    $manifest= <<<'PHP'
$files=[];foreach(['app','bin','config','database','public'] as $folder){foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder,FilesystemIterator::SKIP_DOTS)) as $file){$path=str_replace('\\','/',$file->getPathname());if(in_array($file->getExtension(),['php','css','js','json'],true)&&!str_starts_with($path,'public/uploads/'))$files[$path]=hash_file('sha256',$path);}}
foreach(['index.php','install.php','router.local.php']as$path)$files[$path]=hash_file('sha256',$path);ksort($files);echo hash('sha256',json_encode($files));
PHP;
    [$code,$fingerprint]=$fixture->php(['-r',$manifest]);if($code!==0)throw new RuntimeException('Runtime manifest failed.');
    $process=proc_open(['python',__DIR__.'/submissions.py'],[0=>['pipe','r'],1=>['pipe','w'],2=>['file',$output.'/client-error.log','a']],$pipes,$repository);
    if(!is_resource($process))throw new RuntimeException('Python load client unavailable.');
    fwrite($pipes[0],json_encode(['baseUrl'=>$fixture->baseUrl,'password'=>$password,'assignmentId'=>(int)trim($assignment),'outputDirectory'=>$output,'smoke'=>$smoke],JSON_THROW_ON_ERROR)."\n");$password=null;
    $summary=['task'=>'P6 synthetic localhost submission load','dateUtc'=>gmdate('c'),'mode'=>'offline synthetic; worker stopped; no provider calls','smoke'=>$smoke,'runtimeFingerprintSha256'=>trim($fingerprint),'environment'=>['php'=>PHP_VERSION,'database'=>'SQLite','server'=>'PHP built-in single-thread Windows loopback server; router.local.php','workerCount'=>0],'phases'=>[]];
    $complete=false;
    while(($line=fgets($pipes[1]))!==false){
        $event=json_decode($line,true,512,JSON_THROW_ON_ERROR);
        if(($event['event']??null)==='ready'){$summary['client']=$event;echo 'Load clients ready; isolated synthetic target.'.PHP_EOL;}
        elseif($event['event']==='phase_start')echo 'Concurrency '.$event['concurrency'].' '.$event['phase'].': '.$event['durationSeconds'].' seconds'.PHP_EOL;
        elseif($event['event']==='progress')echo 'Concurrency '.$event['concurrency'].' '.$event['phase'].' elapsed '.$event['elapsedSeconds'].' seconds'.PHP_EOL;
        elseif($event['event']==='phase_end'){
            [$code,$counts]=$fixture->php(['-r',$snapshot]);if($code!==0)throw new RuntimeException('Load database snapshot failed.');
            $result=$event['result'];$result['persisted']=json_decode($counts,true,512,JSON_THROW_ON_ERROR);$summary['phases'][]=$result;
            file_put_contents($output.'/summary.json',json_encode($summary,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
            echo 'Accepted '.$result['accepted'].'/'.$result['requests'].'; p95 '.$result['latencyMilliseconds']['p95'].' ms; queue '.$result['persisted']['queued'].PHP_EOL;
            fwrite($pipes[0],"continue\n");
        }elseif($event['event']==='complete')$complete=true;
        elseif($event['event']==='error')throw new RuntimeException('Load client failed: '.$event['reason']);
    }
    fclose($pipes[0]);fclose($pipes[1]);$exit=proc_close($process);if($exit!==0||!$complete)throw new RuntimeException('Load client did not complete.');
    $verify= <<<'PHP'
require 'config/config.php';require 'config/database.php';
$db=Database::getInstance();$invalid=0;$bytes=0;$root=getenv('SCAN_PRIVATE_DIR');
foreach($db->query('SELECT storage_key,size_bytes,sha256 FROM submission_files')as$row){$path=$root.'/'.$row['storage_key'];if(!is_file($path)||filesize($path)!==(int)$row['size_bytes']||hash_file('sha256',$path)!==$row['sha256'])$invalid++;$bytes+=(int)$row['size_bytes'];}
$log=is_file('server.log')?file_get_contents('server.log'):'';
echo json_encode(['invalidPrivateFiles'=>$invalid,'totalPrivateBytes'=>$bytes,'databaseLockMessages'=>substr_count($log,'database is locked'),'academicSaveFailureMessages'=>substr_count($log,'Academic submission save failed')],JSON_THROW_ON_ERROR);
PHP;
    [$code,$validation]=$fixture->php(['-r',$verify]);if($code!==0)throw new RuntimeException('Load durability verification failed.');
    $summary['durability']=json_decode($validation,true,512,JSON_THROW_ON_ERROR);
    $accepted=array_sum(array_column($summary['phases'],'accepted'));
    $last=$summary['phases'][count($summary['phases'])-1]['persisted'];
    $summary['acceptedIncludingWarmup']=$accepted;
    $summary['durability']['oneRevisionAndAttemptPerAcceptedUpload']=$last['submission_files']===$accepted&&$last['scan_attempts']===$accepted;
    $summary['limits']=['Worker deliberately stopped: queue growth is measured, provider capacity/queue-drain time is not measured.','Each phase uses the same growing disposable database; warm-up records remain and are counted in durability, excluded from measured-phase latency/throughput.','Loopback HTTP timings include 4 KiB multipart transfer; transfer-excluded target is not separately instrumented.','Single-thread PHP development server results are not production capacity.','No injected DB lock/disk fault, real provider load, stakeholder acceptance or second-machine reproduction is claimed.'];
    file_put_contents($output.'/summary.json',json_encode($summary,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
    echo 'Load run complete. Sanitized summary and per-request CSV: '.$output.PHP_EOL;
    if(!$summary['durability']['oneRevisionAndAttemptPerAcceptedUpload']||$summary['durability']['invalidPrivateFiles']!==0)exit(1);
}catch(Throwable $error){fwrite(STDERR,'Load experiment failed: '.$error->getMessage().PHP_EOL);exit(1);}
