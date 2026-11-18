<?php
declare(strict_types=1);

final class ScanFixture
{
    public PDO $db;
    public function __construct()
    {
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        require_once dirname(__DIR__,2).'/database/schema.php';
        Schema::install($this->db,'sqlite');
        $this->db->exec("INSERT INTO users(id,username,email,password,full_name,role) VALUES
            (1,'admin','admin@example.test','unused','Fixture Admin','admin'),
            (2,'teacher','teacher@example.test','unused','Fixture Teacher','teacher'),
            (3,'student','student@example.test','unused','Fixture Student','student'),
            (4,'other','other@example.test','unused','Other Teacher','teacher');
            INSERT INTO courses(id,title,slug,teacher_id) VALUES(1,'Fixture Course','fixture',2);
            INSERT INTO assignments(id,course_id,title) VALUES(1,1,'Fixture Assignment'),(2,1,'Second Assignment');
            INSERT INTO submissions(id,assignment_id,student_id) VALUES(1,1,3),(2,2,3)");
    }
    public function migrate(): void
    {
        require_once dirname(__DIR__,2).'/app/scan/bootstrap.php';
        ScanMigration::apply($this->db);
    }
    public function repository(): ScanAttemptRepository
    {
        return new ScanAttemptRepository($this->db,static fn(): string=>'2026-11-18T00:00:00Z');
    }
    public function revision(int $submission=1): int
    {
        return $this->repository()->registerFileRevision($submission,[
            'storageKey'=>bin2hex(random_bytes(16)), 'originalName'=>'fixture.txt',
            'sha256'=>hash('sha256','synthetic'), 'sizeBytes'=>9, 'mimeType'=>'text/plain']);
    }
}

function scanCheck(bool $condition,string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function scanThrows(callable $work): void
{
    try { $work(); } catch (Throwable) { return; }
    throw new RuntimeException('Expected rejection.');
}
