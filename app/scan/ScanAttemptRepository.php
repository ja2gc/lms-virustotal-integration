<?php
declare(strict_types=1);

final class ScanAnalysisReused extends DomainException {}

final class ScanAttemptRepository
{
    private Closure $clock;
    public function __construct(private PDO $db, ?callable $clock=null)
    {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME)!=='sqlite') throw new InvalidArgumentException('SQLite required.');
        $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
        $db->exec('PRAGMA foreign_keys=ON');
        $db->exec('PRAGMA busy_timeout=5000');
        $this->clock=Closure::fromCallable($clock ?? static fn():string=>gmdate('Y-m-d\TH:i:s\Z'));
    }
    private function query(string $sql,array $params=[]): PDOStatement
    {
        $stmt=$this->db->prepare($sql); $stmt->execute($params); return $stmt;
    }
    private function transaction(callable $work): mixed
    {
        // A short write lock serializes pointer allocation and claims; never perform file/HTTP work here.
        $nested=$this->db->inTransaction();
        $this->db->exec($nested?'SAVEPOINT scan_repository':'BEGIN IMMEDIATE');
        try { $result=$work(); $this->db->exec($nested?'RELEASE SAVEPOINT scan_repository':'COMMIT'); return $result; }
        catch(Throwable $error) {
            $this->db->exec($nested?'ROLLBACK TO SAVEPOINT scan_repository':'ROLLBACK');
            if ($nested) $this->db->exec('RELEASE SAVEPOINT scan_repository');
            throw $error;
        }
    }
    private static function utc(mixed $value): string
    {
        if (!is_string($value)) throw new InvalidArgumentException('UTC timestamp required.');
        $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z',$value,new DateTimeZone('UTC'));
        if (!$date || $date->format('Y-m-d\TH:i:s\Z')!==$value) throw new InvalidArgumentException('UTC timestamp required.');
        return $value;
    }
    private function now(): string { return self::utc(($this->clock)()); }

    /** Member 1 calls after immutable bytes are written. On rejection the caller removes only its new file. */
    public function registerFileRevision(int $submissionId,array $file): int
    {
        if (!is_string($file['storageKey']??null) || !preg_match('/\A[a-f0-9]{32,64}\z/',$file['storageKey'])
            || !is_string($file['sha256']??null) || !preg_match('/\A[a-f0-9]{64}\z/',$file['sha256'])
            || !is_int($file['sizeBytes']??null) || $file['sizeBytes']<0
            || !is_string($file['originalName']??null) || $file['originalName']==='' || strlen($file['originalName'])>255
            || preg_match('/[\x00-\x1f\x7f]/',$file['originalName'])
            || !is_string($file['mimeType']??null) || !preg_match('~\A[a-z0-9.+-]+/[a-z0-9.+-]+\z~i',$file['mimeType'])) throw new InvalidArgumentException('Invalid file metadata.');
        return $this->transaction(function()use($submissionId,$file):int {
            if (!$this->query('SELECT id FROM submissions WHERE id=?',[$submissionId])->fetchColumn()) throw new InvalidArgumentException('Submission missing.');
            $number=(int)$this->query('SELECT COALESCE(MAX(revision_number),0)+1 FROM submission_files WHERE submission_id=?',[$submissionId])->fetchColumn();
            $now=$this->now();
            $this->query('INSERT INTO submission_files(submission_id,revision_number,storage_key,original_name,sha256,size_bytes,mime_type,created_at) VALUES(?,?,?,?,?,?,?,?)',
                [$submissionId,$number,$file['storageKey'],$file['originalName'],$file['sha256'],$file['sizeBytes'],$file['mimeType'],$now]);
            $fileId=(int)$this->db->lastInsertId();
            $attemptId=$this->enqueue($fileId,null,'automatic','automatic:'.$fileId,$now);
            $this->query('UPDATE submission_files SET active_scan_attempt_id=? WHERE id=?',[$attemptId,$fileId]);
            $this->query('UPDATE submissions SET current_file_id=? WHERE id=?',[$fileId,$submissionId]);
            return $fileId;
        });
    }
    private function enqueue(int $fileId,?int $actor,string $origin,string $token,string $now): int
    {
        $this->query('INSERT INTO scan_attempts(submission_file_id,requested_by,origin,idempotency_key,requested_at,updated_at,next_run_at) VALUES(?,?,?,?,?,?,?)',[$fileId,$actor,$origin,$token,$now,$now,$now]);
        return (int)$this->db->lastInsertId();
    }
    public function requestRescan(int $submissionId,int $actorId,string $requestToken): array
    {
        if (!preg_match('/\A[a-f0-9]{32,128}\z/',$requestToken)) throw new InvalidArgumentException('Invalid request token.');
        return $this->transaction(function()use($submissionId,$actorId,$requestToken):array {
            $context=$this->getSubmissionContext($submissionId);
            $actor=$this->query('SELECT role FROM users WHERE id=?',[$actorId])->fetchColumn();
            if (!$context || !($actor==='admin' || ($actor==='teacher' && $context['teacherId']===$actorId))) throw new DomainException('Rescan forbidden.');
            $old=$this->query('SELECT a.*,f.submission_id FROM scan_attempts a JOIN submission_files f ON f.id=a.submission_file_id WHERE a.idempotency_key=?',[$requestToken])->fetch();
            if ($old) {
                if ((int)$old['submission_id']!==$submissionId || (int)$old['requested_by']!==$actorId) throw new DomainException('Request token ownership mismatch.');
                return ['attemptId'=>(int)$old['id'],'created'=>false,'processingState'=>$old['state']];
            }
            if ($context['fileId']===null) throw new InvalidArgumentException('Submission has no attachment.');
            $id=$this->enqueue($context['fileId'],$actorId,'teacher_rescan',$requestToken,$this->now());
            $this->query('UPDATE submission_files SET active_scan_attempt_id=? WHERE id=?',[$id,$context['fileId']]);
            return ['attemptId'=>$id,'created'=>true,'processingState'=>'queued'];
        });
    }
    private static function evidenceMode(?string $analysisId): ?string
    {
        if ($analysisId===null) return null;
        return preg_match('/\Afixture-[a-f0-9]{64}-[a-f0-9]{16}\z/',$analysisId)?'fixture':'provider';
    }
    public function getSubmissionContext(int $submissionId): ?array
    {
        $row=$this->query('SELECT s.id,s.student_id,s.file_path,c.id AS course_id,c.teacher_id,f.id AS file_id,a.id AS attempt_id,a.state,a.advisory_status,a.reason,a.completed_at,a.analysis_id FROM submissions s JOIN assignments x ON x.id=s.assignment_id JOIN courses c ON c.id=x.course_id LEFT JOIN submission_files f ON f.id=s.current_file_id AND f.submission_id=s.id LEFT JOIN scan_attempts a ON a.id=f.active_scan_attempt_id AND a.submission_file_id=f.id WHERE s.id=?',[$submissionId])->fetch();
        if (!$row) return null;
        $legacy=$row['file_id']===null && !empty($row['file_path']);
        return ['contractVersion'=>1,'submissionId'=>(int)$row['id'],'studentId'=>(int)$row['student_id'],'courseId'=>(int)$row['course_id'],'teacherId'=>(int)$row['teacher_id'],
            'fileId'=>$row['file_id']===null?null:(int)$row['file_id'],'attemptId'=>$row['attempt_id']===null?null:(int)$row['attempt_id'],
            'processingState'=>$row['file_id']===null?($legacy?'failed':'not_applicable'):($row['state']??'failed'),'advisoryStatus'=>$legacy?'Unresolved':$row['advisory_status'],
            'reason'=>$row['file_id']===null?($legacy?'legacy_file_pending_migration':'no_attachment'):($row['reason']??'missing_evidence'),'checkedAt'=>$row['completed_at'],'evidenceMode'=>self::evidenceMode($row['analysis_id'])];
    }
    public function getFileContext(int $fileId): ?array
    {
        $row=$this->query('SELECT f.*,s.student_id,c.id AS course_id,c.teacher_id FROM submission_files f JOIN submissions s ON s.id=f.submission_id JOIN assignments a ON a.id=s.assignment_id JOIN courses c ON c.id=a.course_id WHERE f.id=?',[$fileId])->fetch();
        if (!$row) return null;
        return ['fileId'=>(int)$row['id'],'submissionId'=>(int)$row['submission_id'],'studentId'=>(int)$row['student_id'],'courseId'=>(int)$row['course_id'],'teacherId'=>(int)$row['teacher_id'],
            'storageKey'=>$row['storage_key'],'originalName'=>$row['original_name'],'mimeType'=>$row['mime_type'],'sizeBytes'=>(int)$row['size_bytes']];
    }
    public function getAttemptHistory(int $fileId): array
    {
        return array_map(static fn(array $r):array=>['attemptId'=>(int)$r['id'],'origin'=>$r['origin'],'processingState'=>$r['state'],'advisoryStatus'=>$r['advisory_status'],'reason'=>$r['reason'],
            'stats'=>$r['stats_json']===null?null:json_decode($r['stats_json'],true,512,JSON_THROW_ON_ERROR),'requestedAt'=>$r['requested_at'],'completedAt'=>$r['completed_at'],'evidenceMode'=>self::evidenceMode($r['analysis_id'])],
            $this->query('SELECT * FROM scan_attempts WHERE submission_file_id=? ORDER BY id DESC',[$fileId])->fetchAll());
    }
    public function claimDue(string $leaseToken,string $nowUtc,string $leaseUntilUtc): ?array
    {
        self::utc($nowUtc); self::utc($leaseUntilUtc);
        if ($leaseToken==='' || strlen($leaseToken)>128 || $leaseUntilUtc<=$nowUtc) throw new InvalidArgumentException('Invalid lease.');
        return $this->transaction(function()use($leaseToken,$nowUtc,$leaseUntilUtc):?array {
            $this->query("UPDATE scan_attempts SET state='failed',advisory_status='Unresolved',reason='start_outcome_unknown',completed_at=?,updated_at=?,lease_token=NULL,lease_until=NULL,next_run_at=NULL WHERE state='running' AND lease_until<=? AND start_in_flight=1 AND analysis_id IS NULL",[$nowUtc,$nowUtc,$nowUtc]);
            $this->query("UPDATE scan_attempts SET state='failed',advisory_status='Unresolved',reason='polling_exhausted',completed_at=?,updated_at=?,lease_token=NULL,lease_until=NULL,next_run_at=NULL WHERE state IN ('queued','waiting','running') AND poll_deadline_at<=? AND (state!='running' OR lease_until<=?)",[$nowUtc,$nowUtc,$nowUtc,$nowUtc]);
            $id=$this->query("SELECT id FROM scan_attempts WHERE ((state IN ('queued','waiting') AND (next_run_at IS NULL OR next_run_at<=?)) OR (state='running' AND lease_until<=?)) ORDER BY id LIMIT 1",[$nowUtc,$nowUtc])->fetchColumn();
            if (!$id) return null;
            $this->query("UPDATE scan_attempts SET state='running',lease_token=?,lease_until=?,updated_at=? WHERE id=?",[$leaseToken,$leaseUntilUtc,$nowUtc,$id]);
            $r=$this->query('SELECT a.*,f.submission_id,f.sha256,f.storage_key FROM scan_attempts a JOIN submission_files f ON f.id=a.submission_file_id WHERE a.id=?',[$id])->fetch();
            return ['attemptId'=>(int)$r['id'],'fileId'=>(int)$r['submission_file_id'],'submissionId'=>(int)$r['submission_id'],'origin'=>$r['origin'],'state'=>$r['state'],'step'=>$r['step'],
                'analysisId'=>$r['analysis_id'],'sha256'=>$r['sha256'],'storageKey'=>$r['storage_key'],'leaseToken'=>$r['lease_token'],'pollDeadlineAt'=>$r['poll_deadline_at'],
                'startInFlight'=>(bool)$r['start_in_flight'],'nextRunAt'=>$r['next_run_at'],'failureCount'=>(int)$r['failure_count']];
        });
    }
    /** A fresh request must not reuse a stored handle for this immutable revision. */
    public function hasAnalysisId(int $fileId,string $analysisId): bool
    {
        return $this->analysisIdExists($fileId,$analysisId);
    }
    private function analysisIdExists(int $fileId,string $analysisId,int $exceptAttempt=0): bool
    {
        $rows=$this->query('SELECT f.sha256,a.analysis_id FROM submission_files f JOIN scan_attempts a ON a.submission_file_id=f.id WHERE f.id=? AND a.id<>? AND a.analysis_id IS NOT NULL',[$fileId,$exceptAttempt])->fetchAll();
        foreach ($rows as $row) {
            if (self::analysisIdentity($analysisId,$row['sha256'])===self::analysisIdentity($row['analysis_id'],$row['sha256'])) return true;
        }
        return false;
    }
    private static function analysisIdentity(string $id,string $sha256): string
    {
        $decoded=base64_decode($id,true);
        if ($decoded!==false && base64_encode($decoded)===$id && preg_match('/\A[a-f0-9]{32}:([1-9][0-9]{0,11})\z/',$decoded,$descriptor)) {
            return 'f-'.$sha256.'-'.$descriptor[1];
        }
        return $id;
    }
    /** Result consumers reload only the durable work they still own. */
    public function getClaimContext(int $attemptId,string $leaseToken): ?array
    {
        $r=$this->query("SELECT a.*,f.submission_id,f.sha256,f.storage_key FROM scan_attempts a JOIN submission_files f ON f.id=a.submission_file_id WHERE a.id=? AND a.lease_token=? AND a.state='running' AND a.lease_until>?",[$attemptId,$leaseToken,$this->now()])->fetch();
        if (!$r) return null;
        return ['attemptId'=>(int)$r['id'],'fileId'=>(int)$r['submission_file_id'],'submissionId'=>(int)$r['submission_id'],'origin'=>$r['origin'],'state'=>$r['state'],'step'=>$r['step'],
            'analysisId'=>$r['analysis_id'],'sha256'=>$r['sha256'],'storageKey'=>$r['storage_key'],'leaseToken'=>$r['lease_token'],'pollDeadlineAt'=>$r['poll_deadline_at'],
            'startInFlight'=>(bool)$r['start_in_flight'],'nextRunAt'=>$r['next_run_at'],'failureCount'=>(int)$r['failure_count']];
    }
    public function saveProgress(int $attemptId,string $leaseToken,array $changes): bool
    {
        $map=['state'=>'state','step'=>'step','analysisId'=>'analysis_id','startInFlight'=>'start_in_flight','pollDeadlineAt'=>'poll_deadline_at','nextRunAt'=>'next_run_at','failureCount'=>'failure_count','reason'=>'reason','leaseUntil'=>'lease_until'];
        if (!$changes || array_diff_key($changes,$map)) throw new InvalidArgumentException('Unsupported progress field.');
        foreach ($changes as $key=>$value) {
            if (in_array($key,['pollDeadlineAt','nextRunAt','leaseUntil'],true) && $value!==null) self::utc($value);
            if ($key==='state' && !in_array($value,['running','waiting'],true)) throw new InvalidArgumentException('Invalid progress state.');
            if ($key==='step' && !in_array($value,['lookup','start','poll','verify','persist'],true)) throw new InvalidArgumentException('Invalid step.');
            if ($key==='startInFlight' && !is_bool($value)) throw new InvalidArgumentException('Boolean required.');
            if ($key==='failureCount' && (!is_int($value)||$value<0)) throw new InvalidArgumentException('Invalid failure count.');
            if ($key==='reason' && (!is_string($value)||!preg_match('/\A[a-z][a-z0-9_]{0,99}\z/',$value))) throw new InvalidArgumentException('Invalid reason.');
            if ($key==='analysisId' && $value!==null && (!is_string($value)||!preg_match('/\A[\x21-\x7e]{1,512}\z/',$value))) throw new InvalidArgumentException('Invalid analysis ID.');
        }
        return $this->transaction(function()use($attemptId,$leaseToken,$changes,$map):bool {
            $claim=$this->getClaimContext($attemptId,$leaseToken);
            if ($claim===null) return false;
            if (isset($changes['analysisId']) && $this->analysisIdExists($claim['fileId'],$changes['analysisId'],$attemptId)) {
                throw new ScanAnalysisReused('Analysis already belongs to another attempt.');
            }
            $set=[]; $values=[];
            foreach ($changes as $key=>$value) { $set[]=$map[$key].'=?'; $values[]=is_bool($value)?(int)$value:$value; }
            $now=$this->now(); $values[]=$now; $values[]=$attemptId; $values[]=$leaseToken; $values[]=$now;
            return $this->query('UPDATE scan_attempts SET '.implode(',',$set).",updated_at=? WHERE id=? AND lease_token=? AND state='running' AND lease_until>?",$values)->rowCount()===1;
        });
    }
    public function saveResult(int $attemptId,string $leaseToken,array $result,array $transition): bool
    {
        $status=$result['status']??null; $state=$transition['state']??null;
        if (!in_array($status,['Flagged','Review Recommended','No Detections Reported','Unresolved'],true)
            || !is_string($result['reason']??null) || !preg_match('/\A[a-z][a-z0-9_]{0,99}\z/',$result['reason'])
            || !is_bool($result['retryable']??null) || !array_key_exists('retryAfter',$result)
            || ($result['retryAfter']!==null && (!is_int($result['retryAfter'])||$result['retryAfter']<0))
            || !in_array($state,['waiting','completed','failed'],true)
            || !is_int($transition['failureCount']??null) || $transition['failureCount']<0
            || !array_key_exists('nextRunAt',$transition) || !array_key_exists('completedAt',$transition)) throw new InvalidArgumentException('Invalid result transition.');
        if ($state==='waiting') {
            self::utc($transition['nextRunAt']);
            if ($transition['completedAt']!==null || !$result['retryable'] || $status!=='Unresolved') throw new InvalidArgumentException('Invalid waiting result.');
        } else {
            self::utc($transition['completedAt']);
            if ($transition['nextRunAt']!==null || ($status!=='Unresolved' && ($state!=='completed'||$result['retryable']))) throw new InvalidArgumentException('Invalid terminal result.');
        }
        $stats=$result['stats']??null;
        if ($stats!==null) {
            $names=['malicious','suspicious','harmless','undetected','timeout','confirmed-timeout','failure','type-unsupported'];
            if (!is_array($stats)||array_diff($names,array_keys($stats))) throw new InvalidArgumentException('Incomplete stats.');
            $stats=array_intersect_key($stats,array_flip($names));
            foreach ($stats as $count) if (!is_int($count)||$count<0) throw new InvalidArgumentException('Invalid stats.');
        }
        $now=$this->now();
        return $this->query("UPDATE scan_attempts SET state=?,advisory_status=?,reason=?,stats_json=?,failure_count=?,next_run_at=?,completed_at=?,updated_at=?,lease_token=NULL,lease_until=NULL WHERE id=? AND lease_token=? AND state='running' AND lease_until>?",[
            $state,$status,$result['reason'],$stats===null?null:json_encode($stats,JSON_THROW_ON_ERROR),$transition['failureCount'],$transition['nextRunAt'],$transition['completedAt'],$now,$attemptId,$leaseToken,$now])->rowCount()===1;
    }
}
