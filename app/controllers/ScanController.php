<?php
declare(strict_types=1);

class ScanController extends Controller
{
    public function status(int $submissionId): void
    {
        $this->serve('GET',function(ScanAttemptRepository $repository)use($submissionId):void {
            $context=$this->context($repository,$submissionId);
            if ($context===null) return;
            $fields=['contractVersion','submissionId','fileId','attemptId','processingState','advisoryStatus','reason','checkedAt'];
            $result=array_intersect_key($context,array_flip($fields));
            if (!in_array($result['processingState'],['not_applicable','queued','running','waiting','completed','failed'],true)) {
                $result['processingState']='failed'; $result['advisoryStatus']='Unresolved'; $result['reason']='unknown_processing_state'; $result['checkedAt']=null;
            }
            if ($result['advisoryStatus']!==null && !in_array($result['advisoryStatus'],['Flagged','Review Recommended','No Detections Reported','Unresolved'],true)) {
                $result['advisoryStatus']='Unresolved'; $result['reason']='unknown_advisory';
            }
            if (in_array($result['processingState'],['completed','failed'],true) && $result['advisoryStatus']===null) {
                $result['advisoryStatus']='Unresolved'; $result['reason']='missing_evidence';
            }
            if ($context['fileId']!==null && (Auth::isTeacher()||Auth::isAdmin())) $result['rescanToken']=$this->rescanToken($submissionId);
            $this->respond(200,$result);
        });
    }
    public function history(int $submissionId): void
    {
        $this->serve('GET',function(ScanAttemptRepository $repository)use($submissionId):void {
            $context=$this->context($repository,$submissionId,true);
            if ($context===null) return;
            $history=$context['fileId']===null?[]:$repository->getAttemptHistory($context['fileId']);
            foreach ($history as &$item) {
                if (!in_array($item['processingState'],['queued','running','waiting','completed','failed'],true)) {
                    $item['processingState']='failed'; $item['advisoryStatus']='Unresolved'; $item['reason']='unknown_processing_state';
                }
                if ($item['advisoryStatus']!==null && !in_array($item['advisoryStatus'],['Flagged','Review Recommended','No Detections Reported','Unresolved'],true)) {
                    $item['advisoryStatus']='Unresolved'; $item['reason']='unknown_advisory';
                }
            }
            unset($item);
            $this->respond(200,['contractVersion'=>1,'submissionId'=>$submissionId,'fileId'=>$context['fileId'],'history'=>$history]);
        });
    }
    public function rescan(int $submissionId): void
    {
        $this->serve('POST',function(ScanAttemptRepository $repository)use($submissionId):void {
            if ($this->context($repository,$submissionId,true)===null) return;
            $token=$_POST['requestToken']??null;
            $tokens=Session::get('scan_rescan_tokens',[]);
            if (!is_string($token) || ($tokens[$token]['submissionId']??null)!==$submissionId) {
                $this->respond(422,['error'=>['code'=>'invalid_request_token','message'=>'Refresh the scan details and try again.']]); return;
            }
            $result=(new ScanAttemptService($repository))->requestRescan($submissionId,(int)Auth::id(),$token);
            $tokens[$token]['used']=true; Session::set('scan_rescan_tokens',$tokens);
            $this->respond($result['created']?202:200,['contractVersion'=>1]+$result);
        });
    }
    private function rescanToken(int $submissionId): string
    {
        $tokens=Session::get('scan_rescan_tokens',[]);
        foreach ($tokens as $token=>$data) if ($data['submissionId']===$submissionId && !$data['used']) return $token;
        $token=bin2hex(random_bytes(32)); $tokens[$token]=['submissionId'=>$submissionId,'used'=>false];
        // ponytail: retain issued tokens for session retries; use signed tokens if session size becomes material.
        Session::set('scan_rescan_tokens',$tokens); return $token;
    }
    private function context(ScanAttemptRepository $repository,int $submissionId,bool $teacherOnly=false): ?array
    {
        $context=$repository->getSubmissionContext($submissionId);
        if ($context===null) { $this->respond(404,['error'=>['code'=>'not_found','message'=>'Submission not found.']]); return null; }
        $allowed=Auth::isAdmin() || (Auth::isTeacher() && $context['teacherId']===(int)Auth::id())
            || (!$teacherOnly && Auth::isStudent() && $context['studentId']===(int)Auth::id());
        if (!$allowed) { $this->respond(403,['error'=>['code'=>'forbidden','message'=>'Access denied.']]); return null; }
        return $context;
    }
    private function serve(string $method,callable $work): void
    {
        if (($_SERVER['REQUEST_METHOD']??'GET')!==$method) { header('Allow: '.$method); $this->respond(405,['error'=>['code'=>'method_not_allowed','message'=>'Request method not allowed.']]); return; }
        if (!Auth::check()) { $this->respond(401,['error'=>['code'=>'authentication_required','message'=>'Sign in to continue.']]); return; }
        try { $work(new ScanAttemptRepository(Database::getInstance())); }
        catch (DomainException) { $this->respond(403,['error'=>['code'=>'forbidden','message'=>'Access denied.']]); }
        catch (InvalidArgumentException) { $this->respond(422,['error'=>['code'=>'invalid_request','message'=>'This submission cannot be rescanned.']]); }
        catch (Throwable) { error_log('Scan HTTP operation failed'); $this->respond(503,['error'=>['code'=>'service_unavailable','message'=>'Scan details are temporarily unavailable.']]); }
    }
    private function respond(int $status,array $body): void
    {
        http_response_code($status); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: private, no-store');
        echo json_encode($body,JSON_THROW_ON_ERROR);
    }
}
