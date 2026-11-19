<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require_once dirname(__DIR__).'/config/config.php';
require_once dirname(__DIR__).'/config/database.php';
require_once dirname(__DIR__).'/app/scan/bootstrap.php';
foreach (['ScanProviderBudget','ScanProviderClient','FixtureScanTransport','ScanResultProcessor','AutomaticSubmissionResult','TeacherRescanResult','ScanWorker'] as $class) require_once dirname(__DIR__).'/app/scan/'.$class.'.php';

try {
    $options=getopt('',['once','resume-provider','fixture-case:']);
    foreach(array_slice($argv,1) as $arg) if (!in_array($arg,['--once','--resume-provider'],true)&&!str_starts_with($arg,'--fixture-case=')) throw new InvalidArgumentException('Unknown worker option.');
    $mode=getenv('SCAN_MODE')?:'fixture';
    if (!in_array($mode,['fixture','live'],true)) throw new InvalidArgumentException('SCAN_MODE must be fixture or live.');
    if ($mode==='live'&&isset($options['fixture-case'])) throw new InvalidArgumentException('Fixture cases require fixture mode.');
    $db=Database::getInstance();
    $minute=filter_var(getenv('SCAN_REQUESTS_PER_MINUTE')?:4,FILTER_VALIDATE_INT);
    $daily=filter_var(getenv('SCAN_REQUESTS_PER_DAY')?:500,FILTER_VALIDATE_INT);
    if ($minute===false||$daily===false) throw new InvalidArgumentException('Invalid provider budget.');
    $budget=new ScanProviderBudget($db,null,$minute,$daily);
    if (isset($options['resume-provider'])) {$budget->resume();echo "Provider pause cleared after operator correction.\n";}
    $key=$mode==='fixture'?'offline-fixture-key':(getenv('VT_API_KEY')?:'');
    $transport=$mode==='fixture'?new FixtureScanTransport($options['fixture-case']??'no-detections'):new CurlTransport();
    $worker=new ScanWorker($db,getenv('SCAN_PRIVATE_DIR')?:ROOT_DIR.'/private/submissions',$key,$transport,null,$minute,$daily);
    echo $mode==='fixture'?"OFFLINE FIXTURE worker; no VirusTotal requests.\n":"LIVE worker; synthetic permitted files only.\n";
    $stop=false;
    if (function_exists('sapi_windows_set_ctrl_handler')) sapi_windows_set_ctrl_handler(static function()use(&$stop):bool {$stop=true;return true;});
    if (function_exists('pcntl_async_signals')) {pcntl_async_signals(true);pcntl_signal(SIGTERM,static function()use(&$stop):void {$stop=true;});pcntl_signal(SIGINT,static function()use(&$stop):void {$stop=true;});}
    do {$worked=$worker->runOnce();if(isset($options['once']))break;if(!$worked)sleep(1);} while(!$stop);
    if (isset($options['once'])) echo $worked?"Processed one due step.\n":"No due work.\n";
} catch(Throwable) {
    fwrite(STDERR,"Worker stopped. Verify scan migration, private storage, mode, provider budget and local key configuration; credentials are never printed.\n");exit(1);
}
