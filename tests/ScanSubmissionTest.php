<?php
declare(strict_types=1);
require_once __DIR__.'/Support/ScanFixture.php';
if (is_file(dirname(__DIR__).'/app/scan/ScanSubmissionService.php')) require_once dirname(__DIR__).'/app/scan/ScanSubmissionService.php';

function submissionFixture(): array
{
    $fixture=new ScanFixture(); $fixture->migrate();
    $root=dirname(__DIR__).'/tmp/tests/submission-'.bin2hex(random_bytes(6));
    mkdir($root,0700,true);
    return [$fixture,$root,new ScanSubmissionService($fixture->db,$root.'/private',static fn():string=>'2026-11-19T00:00:00Z')];
}
function submissionUpload(string $root,string $text='Synthetic reflection',string $name='reflection.txt'): array
{
    $path=$root.'/source-'.bin2hex(random_bytes(4)); file_put_contents($path,$text);
    return ['tmpPath'=>$path,'originalName'=>$name,'sizeBytes'=>strlen($text),'mimeType'=>'text/plain'];
}
return ['group'=>'submission','tests'=>[
    'private immutable copies create distinct revisions and queue once per file' => function():void {
        [$f,$root,$service]=submissionFixture(); $input=submissionUpload($root);
        $one=$service->saveFileRevision(1,$input); $two=$service->saveFileRevision(2,$input);
        scanCheck($one!==$two,'Same hash merged files.');
        $rows=$f->db->query('SELECT * FROM submission_files ORDER BY id')->fetchAll();
        scanCheck($rows[0]['storage_key']!==$rows[1]['storage_key'],'Storage is not unique.');
        foreach($rows as $row) scanCheck(hash_file('sha256',$root.'/private/'.$row['storage_key'])===$row['sha256'],'Stored bytes/hash mismatch.');
        scanCheck((int)$f->db->query('SELECT count(*) FROM scan_attempts')->fetchColumn()===2,'Queue count mismatch.');
        scanCheck(is_file($input['tmpPath']),'Source was removed.');
    },
    'failed registration removes only newly written bytes' => function():void {
        [$f,$root,$service]=submissionFixture(); $input=submissionUpload($root);
        $service->saveFileRevision(1,$input); $before=glob($root.'/private/*');
        scanThrows(fn()=>$service->saveFileRevision(999,$input));
        scanCheck(glob($root.'/private/*')===$before,'Failed registration leaked or removed files.');
    },
    'academic text updates and failed replacements retain committed file and content' => function():void {
        [$f,$root,$service]=submissionFixture(); $input=submissionUpload($root);
        $service->saveAcademicSubmission(1,3,'First',$input);
        $before=$f->db->query('SELECT * FROM submissions WHERE id=1')->fetch();
        $service->saveAcademicSubmission(1,3,'Text only',null);
        $after=$f->db->query('SELECT * FROM submissions WHERE id=1')->fetch();
        scanCheck($after['current_file_id']===$before['current_file_id'] && $after['content']==='Text only','Text update lost file.');
        $input['sizeBytes']++;
        scanThrows(fn()=>$service->saveAcademicSubmission(1,3,'Lost text',$input));
        $after=$f->db->query('SELECT * FROM submissions WHERE id=1')->fetch();
        scanCheck($after['content']==='Text only' && $after['current_file_id']===$before['current_file_id'],'Failed replacement mutated saved work.');
    },
    'file validation rejects extension MIME size path and HTTP upload forgery' => function():void {
        [$f,$root,$service]=submissionFixture();
        foreach ([['originalName'=>'x.php'],['originalName'=>'../x.txt'],['originalName'=>"x\n.txt"],['sizeBytes'=>999],['mimeType'=>'application/pdf']] as $change) {
            $input=array_replace(submissionUpload($root),$change); scanThrows(fn()=>$service->saveFileRevision(1,$input));
        }
        scanThrows(fn()=>$service->saveFileRevision(1,submissionUpload($root,'<?php echo 1;','x.pdf')));
        $local=submissionUpload($root);
        scanThrows(fn()=>ScanSubmissionService::validateHttpUpload(['name'=>'x.txt','tmp_name'=>$local['tmpPath'],'size'=>$local['sizeBytes'],'error'=>UPLOAD_ERR_OK]));
        foreach([UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_PARTIAL,UPLOAD_ERR_CANT_WRITE] as $error) scanThrows(fn()=>ScanSubmissionService::validateHttpUpload(['error'=>$error]));
        scanCheck(ScanSubmissionService::validateHttpUpload(['error'=>UPLOAD_ERR_NO_FILE])===null,'No-file upload rejected.');
    },
    'legacy import is contained dry by default and repeatable without deleting originals' => function():void {
        [$f,$root,$service]=submissionFixture(); mkdir($root.'/legacy'); file_put_contents($root.'/legacy/old.txt','Synthetic reflection');
        $f->db->exec("UPDATE submissions SET file_path='submissions/old.txt',file_name='old.txt' WHERE id=1");
        $dry=$service->importLegacy($root.'/legacy',false);
        scanCheck($dry['eligible']===1 && $dry['imported']===0,'Dry-run changed records.');
        $apply=$service->importLegacy($root.'/legacy',true);
        scanCheck($apply['imported']===1 && $service->importLegacy($root.'/legacy',true)['imported']===0,'Import is not repeatable.');
        scanCheck(is_file($root.'/legacy/old.txt'),'Legacy original removed.');
        $f->db->exec("UPDATE submissions SET file_path='submissions/../old.txt',file_name='old.txt' WHERE id=2");
        scanCheck($service->importLegacy($root.'/legacy',true)['failed']===1,'Traversal accepted.');
        $f->db->exec("UPDATE submissions SET current_file_id=NULL,file_path='submissions/old.txt',file_name='old.txt' WHERE id=2; CREATE TABLE import_guard(parent_id INTEGER REFERENCES users(id) DEFERRABLE INITIALLY DEFERRED); CREATE TRIGGER fail_import_commit AFTER INSERT ON submission_files BEGIN INSERT INTO import_guard VALUES(999); END");
        $before=glob($root.'/private/*');
        $failed=$service->importLegacy($root.'/legacy',true);
        scanCheck($failed['failed']===1 && $failed['imported']===0,'Failed import reported success.');
        scanCheck(glob($root.'/private/*')===$before,'Failed import commit leaked bytes.');
    },
    'commit failure rolls back academic metadata and removes only new file bytes' => function():void {
        [$f,$root,$service]=submissionFixture();
        $service->saveAcademicSubmission(1,3,'Saved',submissionUpload($root));
        $before=glob($root.'/private/*');
        $f->db->exec('CREATE TABLE commit_guard(parent_id INTEGER REFERENCES users(id) DEFERRABLE INITIALLY DEFERRED); CREATE TRIGGER fail_file_commit AFTER INSERT ON submission_files BEGIN INSERT INTO commit_guard VALUES(999); END');
        scanThrows(fn()=>$service->saveAcademicSubmission(1,3,'Not saved',submissionUpload($root,'New synthetic text')));
        scanCheck(glob($root.'/private/*')===$before,'Commit failure leaked or removed bytes.');
        scanCheck($f->db->query('SELECT content FROM submissions WHERE id=1')->fetchColumn()==='Saved','Commit failure lost prior text.');
        scanCheck((int)$f->db->query('SELECT count(*) FROM submission_files')->fetchColumn()===1,'Commit failure retained new revision.');
    },
    'text-only saves work before scan migration and storage errors do not accept uploads' => function():void {
        $f=new ScanFixture();
        $root=dirname(__DIR__).'/tmp/tests/unmigrated-'.bin2hex(random_bytes(6)); mkdir($root,0700,true);
        $service=new ScanSubmissionService($f->db,$root.'/private');
        $service->saveAcademicSubmission(1,3,'Before migration',null);
        scanCheck($f->db->query('SELECT content FROM submissions WHERE id=1')->fetchColumn()==='Before migration','Baseline save requires migration.');
        scanThrows(fn()=>$service->saveAcademicSubmission(1,3,'Rejected',submissionUpload($root)));
        scanCheck($f->db->query('SELECT content FROM submissions WHERE id=1')->fetchColumn()==='Before migration','Missing schema accepted partial upload.');
        scanCheck(glob($root.'/private/*')===[],'Missing schema leaked bytes.');
        $f->migrate(); file_put_contents($root.'/blocked','synthetic');
        $blocked=new ScanSubmissionService($f->db,$root.'/blocked');
        scanThrows(fn()=>$blocked->saveFileRevision(1,submissionUpload($root)));
    },
    'PDF and DOCX bytes match permitted MIME mappings while arbitrary ZIP remains rejected' => function():void {
        [$f,$root,$service]=submissionFixture();
        $pdf=submissionUpload($root,"%PDF-1.4\n% Synthetic fixture\n%%EOF",'fixture.pdf'); unset($pdf['mimeType']);
        $service->saveFileRevision(1,$pdf);
        $docxBytes=base64_decode('UEsDBBQAAAAIABt6c13HHBc8CgAAAAgAAAATAAAAW0NvbnRlbnRfVHlwZXNdLnhtbLMJqSxILda3AwBQSwMEFAAAAAgAG3pzXYLZHNUSAAAAEAAAAAsAAABfcmVscy8ucmVsc7MJSs1JLMnMzyvOyCwo1rcDAFBLAwQUAAAACAAbenNdX1vRTA0AAAALAAAAEQAAAHdvcmQvZG9jdW1lbnQueG1ss0nJTy7NTc0r0bcDAFBLAQIUABQAAAAIABt6c13HHBc8CgAAAAgAAAATAAAAAAAAAAAAAACAAQAAAABbQ29udGVudF9UeXBlc10ueG1sUEsBAhQAFAAAAAgAG3pzXYLZHNUSAAAAEAAAAAsAAAAAAAAAAAAAAIABOwAAAF9yZWxzLy5yZWxzUEsBAhQAFAAAAAgAG3pzXV9b0UwNAAAACwAAABEAAAAAAAAAAAAAAIABdgAAAHdvcmQvZG9jdW1lbnQueG1sUEsFBgAAAAADAAMAuQAAALIAAAAAAA==',true);
        $docx=submissionUpload($root,$docxBytes,'fixture.docx'); unset($docx['mimeType']);
        $id=$service->saveFileRevision(2,$docx);
        $row=$f->db->query('SELECT mime_type FROM submission_files WHERE id='.$id)->fetchColumn();
        scanCheck($row==='application/vnd.openxmlformats-officedocument.wordprocessingml.document','DOCX MIME is not canonical.');
        $docx['originalName']='fixture.zip'; scanThrows(fn()=>$service->saveFileRevision(2,$docx));
        $invalid=submissionUpload($root,'Not a ZIP container','fixture.docx'); unset($invalid['mimeType']);
        scanThrows(fn()=>$service->saveFileRevision(2,$invalid));
    },
    'matched database and private-file backup restores bytes identities history and access context' => function():void {
        [$f,$root,$service]=submissionFixture();
        $id=$service->saveAcademicSubmission(1,3,'Restore fixture',submissionUpload($root));
        $service->saveAcademicSubmission(1,3,'Current revision',submissionUpload($root,'Synthetic revision two'));
        $before=$f->repository()->getSubmissionContext($id);
        $fileRows=$f->db->query('SELECT * FROM submission_files ORDER BY id')->fetchAll();
        mkdir($root.'/backup',0700); mkdir($root.'/restored',0700);
        // A consistent SQLite snapshot plus immutable byte files; no live database is involved.
        $backup=$root.'/backup/database.sqlite';
        $f->db->exec('VACUUM INTO '.$f->db->quote($backup));
        foreach($fileRows as $row) copy($root.'/private/'.$row['storage_key'],$root.'/backup/'.$row['storage_key']);
        copy($backup,$root.'/restored/database.sqlite');
        foreach($fileRows as $row) copy($root.'/backup/'.$row['storage_key'],$root.'/restored/'.$row['storage_key']);
        $restored=new PDO('sqlite:'.$root.'/restored/database.sqlite');
        $repository=new ScanAttemptRepository($restored);
        scanCheck($repository->getSubmissionContext($id)===$before,'Restored current pointers or ownership changed.');
        foreach($fileRows as $row) {
            $context=$repository->getFileContext((int)$row['id']);
            scanCheck($context['studentId']===3 && $context['teacherId']===2 && $context['courseId']===1,'Restored authorization context differs.');
            scanCheck(hash_file('sha256',$root.'/restored/'.$row['storage_key'])===$row['sha256'],'Restored byte identity differs.');
            scanCheck(file_get_contents($root.'/restored/'.$row['storage_key'])===file_get_contents($root.'/private/'.$row['storage_key']),'Restored bytes differ.');
            scanCheck(count($repository->getAttemptHistory((int)$row['id']))===1,'Restored scan history missing.');
        }
        scanThrows(fn()=>$repository->requestRescan($id,4,str_repeat('e',64)));
    },
]];
