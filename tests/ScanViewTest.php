<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/helpers/functions.php';
require_once __DIR__.'/Support/ScanWebFixture.php';

return ['group'=>'ui','tests'=>[
    'scan presentation separates pending no-file and advisory states conservatively' => function (): void {
        $cases=[['queued',null,'Queued'],['waiting',null,'Waiting / retrying'],['not_applicable',null,'Not applicable'],['completed','No Detections Reported','No Detections Reported'],['completed','Flagged','Flagged'],['failed','Safe','Unresolved']];
        foreach ($cases as [$state,$advisory,$expected]) {
            $context=['processingState'=>$state,'advisoryStatus'=>$advisory,'reason'=>'fixture_reason','checkedAt'=>null];
            if (!str_contains(scanDisplay($context),$expected)) throw new RuntimeException('Incorrect scan label.');
        }
        if (scanDisplay(['processingState'=>'alien','advisoryStatus'=>'No Detections Reported'])!=='Unresolved') throw new RuntimeException('Unknown state accepted.');
        if (scanDisplay(['processingState'=>'failed','advisoryStatus'=>'No Detections Reported'])!=='Unresolved') throw new RuntimeException('Failed processing produced favorable advisory.');
        if (!str_contains(scanEvidenceLabel(['evidenceMode'=>'fixture']),'Offline fixture')) throw new RuntimeException('Fixture provenance hidden.');
    },
    'scan history escapes provider and display strings without application safety claims' => function (): void {
        $history=[['attemptId'=>1,'origin'=>'automatic','processingState'=>'completed','advisoryStatus'=>'Flagged','reason'=>'<script>bad</script>','checkedAt'=>null,'stats'=>['malicious'=>1],'evidenceMode'=>'fixture']];
        ob_start(); require dirname(__DIR__).'/app/views/scan/_history.php'; $html=ob_get_clean();
        if (str_contains($html,'<script>bad') || !str_contains($html,'Flagged') || !str_contains($html,'malicious') || !str_contains($html,'Offline fixture')) throw new RuntimeException('History evidence missing or unescaped.');
    },
    'submission templates use file IDs and preserve input with aligned upload policy' => function (): void {
        $root=dirname(__DIR__);
        $student=file_get_contents($root.'/app/views/student/submit_assignment.php');
        $teacher=file_get_contents($root.'/app/views/teacher/grade.php');
        if (!str_contains($student,'uploadError') || !str_contains($student,'accept=".pdf,.txt,.docx"') || !str_contains($student,"existing['content']")) throw new RuntimeException('Form policy/input preservation absent.');
        if (str_contains($teacher,'uploadUrl(') || !str_contains($teacher,'renderSubmissionScan')) throw new RuntimeException('Legacy download bypass or missing scan review.');
    },
    'authorized real HTTP pages render scan state separate from grading with private links' => function (): void {
        $web=scanWebFixture(); $web->login('student'); $web->login('teacher');
        foreach ([['student','student/submitAssignment/1'],['student','assignment/view/1'],['teacher','teacher/gradeSubmission/1']] as [$role,$route]) {
            $page=$web->request($role,$route); WebFixture::pageOk($page,$route);
            if (!str_contains($page['body'],'File security advisory') || !str_contains($page['body'],'Queued') || !str_contains($page['body'],'submissionFile/download/1')) throw new RuntimeException('Stored scan state/private download missing on '.$route);
            if (str_contains($page['body'],'public/uploads/submissions/')) throw new RuntimeException('Static attachment bypass rendered.');
        }
        $teacher=$web->request('teacher','teacher/gradeSubmission/1');
        if (!str_contains($teacher['body'],'Request a fresh scan') || !str_contains($teacher['body'],'Scan history and counts')) throw new RuntimeException('Teacher review controls missing.');
        $student=$web->request('student','student/submitAssignment/1');
        if (str_contains($student['body'],'Request a fresh scan') || !str_contains($student['body'],'Synthetic content')) throw new RuntimeException('Student rescan leak or content lost.');
    },
    'invalid new multipart upload preserves input without pretending an existing submission' => function (): void {
        $web=scanWebFixture(); $web->login('student');
        [$code]=$web->php(['-r',"require 'config/config.php';require 'config/database.php';Database::insert('assignments',['id'=>3,'course_id'=>1,'title'=>'New fixture assignment']);"]);
        if ($code!==0) throw new RuntimeException('Cannot prepare new assignment.');
        $page=$web->request('student','student/submitAssignment/3');
        $rejected=$web->request('student','student/submitAssignment/3',['content'=>'Preserved fictional response','_csrf_token'=>WebFixture::csrf($page)],['name'=>'invalid.php','bytes'=>'Synthetic rejected attachment']);
        if ($rejected['status']!==422 || !str_contains($rejected['body'],'Preserved fictional response')) throw new RuntimeException('Invalid upload lost input or returned wrong status.');
        if (preg_match('/Warning|Fatal error|Uncaught|Update submission|Awaiting grade/',$rejected['body'])) throw new RuntimeException('New rejection pretends an existing submission or emits warnings.');
    },
]];
