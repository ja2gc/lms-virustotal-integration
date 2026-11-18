<?php
declare(strict_types=1);

class SubmissionFileController extends Controller
{
    public function download(int $fileId): void
    {
        if (($_SERVER['REQUEST_METHOD']??'GET')!=='GET') { header('Allow: GET'); $this->error(405,'method_not_allowed'); return; }
        if (!Auth::check()) { $this->error(401,'authentication_required'); return; }
        try { $file=(new ScanAttemptRepository(Database::getInstance()))->getFileContext($fileId); }
        catch (Throwable) { error_log('Attachment context read failed'); $this->error(503,'service_unavailable'); return; }
        if ($file===null) { $this->error(404,'not_found'); return; }
        $allowed=Auth::isAdmin() || (Auth::isTeacher() && $file['teacherId']===(int)Auth::id()) || (Auth::isStudent() && $file['studentId']===(int)Auth::id());
        if (!$allowed) { $this->error(403,'forbidden'); return; }
        $root=realpath(getenv('SCAN_PRIVATE_DIR')?:ROOT_DIR.'/private/submissions');
        $public=realpath(PUBLIC_DIR);
        if ($root!==false && $public!==false && ($root===$public || str_starts_with($root,$public.DIRECTORY_SEPARATOR))) { $this->error(503,'service_unavailable'); return; }
        if ($root===false || !preg_match('/\A[a-f0-9]{32,64}\z/',$file['storageKey'])) { $this->error(404,'not_found'); return; }
        $candidate=$root.DIRECTORY_SEPARATOR.$file['storageKey']; $path=realpath($candidate);
        if ($path===false || !str_starts_with($path,$root.DIRECTORY_SEPARATOR) || is_link($candidate) || !is_file($path) || !is_readable($path)) { $this->error(404,'not_found'); return; }
        $handle=@fopen($path,'rb');
        if ($handle===false) { $this->error(404,'not_found'); return; }
        $stat=fstat($handle);
        if ($stat===false || $stat['size']!==$file['sizeBytes']) { fclose($handle); $this->error(404,'not_found'); return; }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="download.bin"; filename*=UTF-8\'\''.rawurlencode($file['originalName']));
        header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store'); header('Content-Length: '.$stat['size']);
        fpassthru($handle); fclose($handle);
    }
    private function error(int $status,string $code): void
    {
        http_response_code($status); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: private, no-store');
        echo json_encode(['error'=>['code'=>$code,'message'=>'Attachment unavailable.']],JSON_THROW_ON_ERROR);
    }
}
