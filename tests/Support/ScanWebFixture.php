<?php
declare(strict_types=1);
require_once __DIR__.'/WebFixture.php';

function scanWebFixture(): WebFixture
{
    $web=new WebFixture();
    [$code,$output]=$web->php(['-r', <<<'PHP'
require 'config/config.php'; require 'config/database.php'; require 'app/scan/bootstrap.php';
$db=Database::getInstance(); ScanMigration::apply($db);
foreach ([1,2] as $id) {
 Database::insert('assignments',['id'=>$id,'course_id'=>$id,'title'=>'Fixture Assignment '.$id]);
 Database::insert('submissions',['id'=>$id,'assignment_id'=>$id,'student_id'=>$id===1?3:5,'content'=>'Synthetic content']);
}
mkdir(ROOT_DIR.'/private/submissions',0700,true);
$key=str_repeat('a',32); $bytes='synthetic attachment'; file_put_contents(ROOT_DIR.'/private/submissions/'.$key,$bytes);
$r=new ScanAttemptRepository($db); $r->registerFileRevision(1,['storageKey'=>$key,'originalName'=>'fixture.txt','sha256'=>hash('sha256',$bytes),'sizeBytes'=>strlen($bytes),'mimeType'=>'text/plain']);
mkdir(ROOT_DIR.'/public/uploads/submissions',0700,true);file_put_contents(ROOT_DIR.'/public/uploads/submissions/legacy.txt','private legacy fixture');
PHP]);
    if ($code!==0) throw new RuntimeException('Scan web fixture failed: '.$output);
    return $web;
}
function scanJson(array $page,int $status): array
{
    if ($page['status']!==$status) throw new RuntimeException('Expected HTTP '.$status.', got '.$page['status']);
    $data=json_decode($page['body'],true,512,JSON_THROW_ON_ERROR);
    if (!is_array($data)) throw new RuntimeException('JSON object required.');
    if (preg_match('~storageKey|private/|api.?key|teacherId|studentId~i',$page['body'])) throw new RuntimeException('Private metadata leaked.');
    return $data;
}
