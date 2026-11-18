<?php
declare(strict_types=1);
require_once __DIR__.'/Support/WebFixture.php';

function environmentFixtureRun(string $contents,string $check): void
{
    $directory=dirname(__DIR__).'/tmp/tests'; if (!is_dir($directory)) mkdir($directory,0700,true);
    $path=$directory.'/env-'.bin2hex(random_bytes(6)).'.example'; file_put_contents($path,$contents);
    $script='require $argv[1]; putenv("VT_API_KEY"); putenv("SCAN_MODE"); putenv("SCAN_PRIVATE_DIR"); '.$check;
    try {
        $process=proc_open([PHP_BINARY,'-r',$script,dirname(__DIR__).'/config/environment.php',$path],
            [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if (!is_resource($process)) throw new RuntimeException('Cannot run environment fixture.');
        fclose($pipes[0]);$output=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        if (proc_close($process)!==0 || $output!=='ok') throw new RuntimeException('Local environment fixture failed; values withheld.');
    } finally { unlink($path); }
}

return ['group'=>'configuration','tests'=>[
    'committed environment example contains no API credential' => function (): void {
        $example=parse_ini_file(dirname(__DIR__).'/.env.example',false,INI_SCANNER_RAW);
        if (!is_array($example) || ($example['VT_API_KEY']??null)!=='' || ($example['SCAN_MODE']??null)!=='fixture') throw new RuntimeException('Keep the committed environment example empty and offline.');
    },
    'HTTP fixtures isolate developer keys live mode and database settings' => function (): void {
        environmentFixtureRun('', <<<'PHP'
require dirname(dirname($argv[1])).'/tests/Support/WebFixture.php';
putenv('vt_api_key=fictional-parent-key');putenv('scan_mode=live');putenv('dOoDlE_dB_tYpE=not-a-driver');putenv('sCaN_pRiVaTe_DiR=C:/fictional/unused');$web=new WebFixture();
[$exit,$output]=$web->php(['-r','echo in_array(getenv("VT_API_KEY"),[false,""],true) && getenv("SCAN_MODE")==="fixture" && getenv("DOODLE_DB_TYPE")==="sqlite" && str_ends_with(str_replace(chr(92),"/",getenv("SCAN_PRIVATE_DIR")),"/private/submissions") ? "isolated" : "unsafe";']);
if($exit!==0||$output!=='isolated')exit(1);echo 'ok';
PHP);
    },
    'local configuration loads literal values without shell interpolation or overriding process settings' => function (): void {
        environmentFixtureRun("# Synthetic example only\nVT_API_KEY=fictional-test-key\nSCAN_MODE=fixture\nSCAN_PRIVATE_DIR=\"C:/fictional/path with spaces\"\n", <<<'PHP'
putenv('SCAN_MODE=live'); LocalEnvironment::load($argv[2]);
if(getenv('VT_API_KEY')!=='fictional-test-key'||getenv('SCAN_MODE')!=='live'||getenv('SCAN_PRIVATE_DIR')!=='C:/fictional/path with spaces')exit(1); echo 'ok';
PHP);
    },
    'local configuration ignores unrelated variables and handles an absent file' => function (): void {
        environmentFixtureRun("UNRELATED_TEST_SETTING=unused\nVT_API_KEY=\"literal-\$HOME\"\n", <<<'PHP'
putenv('UNRELATED_TEST_SETTING');LocalEnvironment::load($argv[2].'.missing');LocalEnvironment::load($argv[2]);
if(getenv('UNRELATED_TEST_SETTING')!==false||getenv('VT_API_KEY')!=='literal-$HOME')exit(1);echo 'ok';
PHP);
    },
    'invalid key arrays fail with a sanitized error' => function (): void {
        environmentFixtureRun("VT_API_KEY[]=fictional-hidden-value\n", <<<'PHP'
try{LocalEnvironment::load($argv[2]);}catch(RuntimeException $e){if($e->getMessage()==='Invalid local environment configuration.'){echo 'ok';exit;}}exit(1);
PHP);
    },
    'web and CLI configuration load a disposable env while HTTP denies env files' => function (): void {
        $web=new WebFixture();
        [$exit]=$web->php(['-r',"file_put_contents('.env',\"VT_API_KEY=fictional-localhost-key\\n\");"]);
        if ($exit!==0) throw new RuntimeException('Cannot prepare fictional environment.');
        [$exit,$output]=$web->php(['-r',"putenv('VT_API_KEY'); require 'config/config.php'; echo getenv('VT_API_KEY')==='fictional-localhost-key'?'ok':'missing';"]);
        if ($exit!==0 || $output!=='ok') throw new RuntimeException('CLI did not load local configuration.');
        foreach (['/.env','/.env.production','/.env.example'] as $path) {
            $page=$web->request('guest',$web->baseUrl.$path);
            if ($page['status']!==404 || str_contains($page['body'],'fictional-localhost-key')) throw new RuntimeException('Environment file exposed over HTTP.');
        }
        WebFixture::pageOk($web->request('guest','auth/login'),'Local environment login');
    },
]];
