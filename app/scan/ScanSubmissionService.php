<?php
declare(strict_types=1);

/** Private byte storage; only the repository writes revision and attempt metadata. */
final class ScanSubmissionService
{
    public const MAX_BYTES = 10485760;
    private array $written = [];
    private ?Closure $clock;

    public function __construct(private PDO $db, private string $privateRoot, ?callable $clock = null)
    {
        $this->clock=$clock===null?null:Closure::fromCallable($clock);
    }

    public static function maxBytes(): int
    {
        $value=getenv('SCAN_MAX_UPLOAD_BYTES');
        if ($value===false || $value==='') return self::MAX_BYTES;
        if (!ctype_digit($value) || (int)$value<1 || (int)$value>self::MAX_BYTES) throw new RuntimeException('Upload limit must be between 1 byte and 10 MiB.');
        return (int)$value;
    }

    /** The HTTP boundary accepts only files PHP identifies as genuine request uploads. */
    public static function validateHttpUpload(?array $file): ?array
    {
        if ($file===null || ($file['error']??null)===UPLOAD_ERR_NO_FILE) return null;
        if (($file['error']??null)!==UPLOAD_ERR_OK) throw new InvalidArgumentException('The attachment upload failed. Select the file again; maximum size is 10 MiB.');
        if (!is_string($file['tmp_name']??null) || !is_uploaded_file($file['tmp_name'])) throw new InvalidArgumentException('The attachment upload is invalid. Select the file again.');
        return self::validateLocalUpload(['tmpPath'=>$file['tmp_name'],'originalName'=>$file['name']??null,'sizeBytes'=>$file['size']??null]);
    }

    /** Trusted CLI/import callers may supply a local file; HTTP callers must use validateHttpUpload first. */
    public static function validateLocalUpload(array $file): array
    {
        $path=$file['tmpPath']??null; $name=$file['originalName']??null; $size=$file['sizeBytes']??null;
        if (!is_string($path) || !is_file($path) || is_link($path) || !is_readable($path)
            || !is_string($name) || $name==='' || strlen($name)>255 || preg_match('~[\x00-\x1f\x7f/\\\\]~',$name)
            || !is_int($size) || $size<0 || $size>self::maxBytes() || filesize($path)!==$size) throw new InvalidArgumentException('Invalid attachment name or size. Maximum size is 10 MiB.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
        $extension=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        $valid=match($extension) {
            'txt'=>in_array($mime,['text/plain','application/x-empty','inode/x-empty'],true),
            'pdf'=>$mime==='application/pdf',
            'docx'=>in_array($mime,['application/zip','application/vnd.openxmlformats-officedocument.wordprocessingml.document'],true) && self::isDocx($path),
            default=>false,
        };
        if (!$valid) throw new InvalidArgumentException('Use a valid PDF, TXT or DOCX attachment. Its content must match its file type.');
        $mime=match($extension) {'txt'=>'text/plain','pdf'=>'application/pdf',default=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document'};
        if (isset($file['mimeType']) && $file['mimeType']!==$mime) throw new InvalidArgumentException('Attachment type does not match its content.');
        return ['tmpPath'=>$path,'originalName'=>$name,'sizeBytes'=>$size,'mimeType'=>$mime];
    }

    private static function isDocx(string $path): bool
    {
        $temporary=null; $owned=false;
        try {
            // PharData requires a filename extension, while immutable storage keys have none.
            $directory=dirname(__DIR__,2).'/tmp/attachment-validation';
            if (!is_dir($directory) && !@mkdir($directory,0700,true) && !is_dir($directory)) return false;
            $temporary=$directory.'/'.bin2hex(random_bytes(24)).'.zip';
            $target=@fopen($temporary,'xb'); $source=@fopen($path,'rb');
            $owned=is_resource($target);
            if ($target===false || $source===false) {
                if (is_resource($target)) fclose($target);
                if (is_resource($source)) fclose($source);
                return false;
            }
            try { $copied=stream_copy_to_stream($source,$target,self::maxBytes()+1); }
            finally { fclose($source); fclose($target); }
            if ($copied!==filesize($path) || $copied>self::maxBytes()) return false;
            $archive=new PharData($temporary);
            return isset($archive['[Content_Types].xml'],$archive['_rels/.rels'],$archive['word/document.xml']);
        } catch (Throwable) { return false; }
        finally { unset($archive); if ($owned) @unlink($temporary); }
    }

    private function root(): string
    {
        if (!is_dir($this->privateRoot) && !@mkdir($this->privateRoot,0700,true) && !is_dir($this->privateRoot)) throw new RuntimeException('Private attachment storage is unavailable.');
        $root=realpath($this->privateRoot);
        $public=realpath(dirname(__DIR__,2).'/public');
        $normalized=static fn(string $path):string=>strtolower(str_replace('\\','/',$path));
        if ($root===false || ($public!==false && ($normalized($root)===$normalized($public) || str_starts_with($normalized($root),$normalized($public).'/')))) throw new RuntimeException('Private attachment storage must be outside public.');
        return $root;
    }

    public function saveFileRevision(int $submissionId, array $validatedUpload): int
    {
        $file=self::validateLocalUpload($validatedUpload);
        $root=$this->root(); $key=bin2hex(random_bytes(24)); $path=$root.DIRECTORY_SEPARATOR.$key;
        $input=@fopen($file['tmpPath'],'rb'); $output=@fopen($path,'xb');
        if ($input===false || $output===false) {
            if (is_resource($input)) fclose($input);
            if (is_resource($output)) { fclose($output); @unlink($path); }
            throw new RuntimeException('Could not store the attachment.');
        }
        try {
            $copied=stream_copy_to_stream($input,$output,self::maxBytes()+1);
            if ($copied!==$file['sizeBytes'] || !fflush($output)) throw new RuntimeException('The attachment could not be stored completely.');
            fclose($input); $input=null; fclose($output); $output=null;
            self::validateLocalUpload(array_replace($file,['tmpPath'=>$path]));
            $hash=hash_file('sha256',$path);
            if ($hash===false) throw new RuntimeException('Could not identify the stored attachment.');
            $id=(new ScanAttemptRepository($this->db,$this->clock))->registerFileRevision($submissionId,[
                'storageKey'=>$key,'originalName'=>$file['originalName'],'sha256'=>$hash,'sizeBytes'=>$copied,'mimeType'=>$file['mimeType']]);
            $this->written[]=$path;
            return $id;
        } catch (Throwable $error) {
            if (is_resource($input)) fclose($input);
            if (is_resource($output)) fclose($output);
            @unlink($path);
            throw $error;
        }
    }

    /** Own the academic transaction so an outer rollback cannot leave new byte files behind. */
    public function saveAcademicSubmission(int $assignmentId,int $studentId,string $content,?array $upload): int
    {
        if ($this->db->inTransaction()) throw new LogicException('Academic save must own its transaction.');
        $firstNew=count($this->written);
        $this->db->beginTransaction();
        try {
            $stmt=$this->db->prepare('SELECT id FROM submissions WHERE assignment_id=? AND student_id=?'); $stmt->execute([$assignmentId,$studentId]);
            $id=$stmt->fetchColumn();
            if ($id) {
                $stmt=$this->db->prepare("UPDATE submissions SET content=?,submitted_at=?,status='submitted' WHERE id=?"); $stmt->execute([$content,date('Y-m-d H:i:s'),$id]);
            } else {
                $stmt=$this->db->prepare('INSERT INTO submissions(assignment_id,student_id,content) VALUES(?,?,?)'); $stmt->execute([$assignmentId,$studentId,$content]); $id=(int)$this->db->lastInsertId();
            }
            if ($upload!==null) {
                $this->saveFileRevision((int)$id,$upload);
                $stmt=$this->db->prepare('UPDATE submissions SET file_path=NULL,file_name=? WHERE id=?'); $stmt->execute([$upload['originalName'],$id]);
            }
            $this->db->commit();
            return (int)$id;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            foreach(array_splice($this->written,$firstNew) as $path) @unlink($path);
            throw $error;
        }
    }

    public function importLegacy(string $legacyRoot,bool $apply=false): array
    {
        $root=realpath($legacyRoot);
        if ($root===false || !is_dir($root)) throw new InvalidArgumentException('Legacy attachment directory is missing.');
        $counts=['eligible'=>0,'imported'=>0,'failed'=>0];
        $rows=$this->db->query("SELECT id,file_path,file_name FROM submissions WHERE current_file_id IS NULL AND file_path IS NOT NULL AND file_path!='' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            try {
                if (!preg_match('~\Asubmissions/([^/\\\\]+)\z~',$row['file_path'],$match) || in_array($match[1],['.','..'],true)) throw new InvalidArgumentException('Invalid legacy path.');
                $candidate=$root.DIRECTORY_SEPARATOR.$match[1]; $path=realpath($candidate);
                if ($path===false || is_link($candidate) || !str_starts_with($path,$root.DIRECTORY_SEPARATOR) || !is_file($path)) throw new InvalidArgumentException('Legacy file is missing or outside storage.');
                $file=self::validateLocalUpload(['tmpPath'=>$path,'originalName'=>$row['file_name']?:$match[1],'sizeBytes'=>filesize($path)]);
                $counts['eligible']++;
                if ($apply) {
                    $firstNew=count($this->written);
                    $this->db->beginTransaction();
                    try {
                        $stmt=$this->db->prepare('SELECT current_file_id FROM submissions WHERE id=?'); $stmt->execute([$row['id']]);
                        $imported=$stmt->fetchColumn()===null;
                        if ($imported) $this->saveFileRevision((int)$row['id'],$file);
                        $this->db->commit();
                        if ($imported) $counts['imported']++;
                    } catch (Throwable $error) {
                        if ($this->db->inTransaction()) $this->db->rollBack();
                        foreach(array_splice($this->written,$firstNew) as $newPath) @unlink($newPath);
                        throw $error;
                    }
                }
            } catch (Throwable) { $counts['failed']++; }
        }
        return $counts;
    }
}
