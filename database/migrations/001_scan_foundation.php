<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec(<<<'SQL'
ALTER TABLE submissions ADD COLUMN current_file_id INTEGER REFERENCES submission_files(id) ON DELETE SET NULL;
CREATE TABLE submission_files (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 submission_id INTEGER NOT NULL REFERENCES submissions(id) ON DELETE CASCADE,
 revision_number INTEGER NOT NULL CHECK(revision_number>0),
 storage_key TEXT NOT NULL UNIQUE,
 original_name TEXT NOT NULL,
 sha256 TEXT NOT NULL CHECK(length(sha256)=64),
 size_bytes INTEGER NOT NULL CHECK(size_bytes>=0),
 mime_type TEXT NOT NULL,
 active_scan_attempt_id INTEGER REFERENCES scan_attempts(id) ON DELETE SET NULL,
 created_at TEXT NOT NULL,
 UNIQUE(submission_id,revision_number)
);
CREATE TABLE scan_attempts (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 submission_file_id INTEGER NOT NULL REFERENCES submission_files(id) ON DELETE CASCADE,
 requested_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
 origin TEXT NOT NULL CHECK(origin IN ('automatic','teacher_rescan')),
 state TEXT NOT NULL DEFAULT 'queued' CHECK(state IN ('queued','running','waiting','completed','failed')),
 step TEXT NOT NULL DEFAULT 'lookup' CHECK(step IN ('lookup','start','poll','verify','persist')),
 analysis_id TEXT,
 advisory_status TEXT CHECK(advisory_status IN ('Flagged','Review Recommended','No Detections Reported','Unresolved')),
 reason TEXT NOT NULL DEFAULT 'scan_queued',
 failure_count INTEGER NOT NULL DEFAULT 0 CHECK(failure_count>=0),
 next_run_at TEXT,
 lease_until TEXT,
 lease_token TEXT,
 requested_at TEXT NOT NULL,
 completed_at TEXT,
 updated_at TEXT NOT NULL,
 poll_deadline_at TEXT,
 start_in_flight INTEGER NOT NULL DEFAULT 0 CHECK(start_in_flight IN (0,1)),
 idempotency_key TEXT NOT NULL UNIQUE,
 contract_version INTEGER NOT NULL DEFAULT 1 CHECK(contract_version=1),
 stats_json TEXT,
 last_http_code INTEGER,
 CHECK(origin!='automatic' OR requested_by IS NULL)
);
CREATE UNIQUE INDEX one_automatic_attempt ON scan_attempts(submission_file_id) WHERE origin='automatic';
CREATE INDEX scan_due ON scan_attempts(state,next_run_at);
CREATE INDEX file_hash ON submission_files(sha256);
CREATE TABLE scan_provider_state (
 id INTEGER PRIMARY KEY CHECK(id=1),
 request_timestamps_json TEXT NOT NULL DEFAULT '[]',
 reservations_json TEXT NOT NULL DEFAULT '[]',
 paused_until TEXT,
 updated_at TEXT NOT NULL
);
INSERT INTO scan_provider_state(id,updated_at) VALUES(1,strftime('%Y-%m-%dT%H:%M:%SZ','now'));
CREATE TRIGGER current_file_owner BEFORE UPDATE OF current_file_id ON submissions
WHEN NEW.current_file_id IS NOT NULL AND NOT EXISTS(SELECT 1 FROM submission_files WHERE id=NEW.current_file_id AND submission_id=NEW.id)
BEGIN SELECT RAISE(ABORT,'File pointer ownership mismatch'); END;
CREATE TRIGGER inserted_current_file_owner BEFORE INSERT ON submissions
WHEN NEW.current_file_id IS NOT NULL
BEGIN SELECT RAISE(ABORT,'New submission must have no file pointer'); END;
CREATE TRIGGER active_attempt_owner BEFORE UPDATE OF active_scan_attempt_id ON submission_files
WHEN NEW.active_scan_attempt_id IS NOT NULL AND NOT EXISTS(SELECT 1 FROM scan_attempts WHERE id=NEW.active_scan_attempt_id AND submission_file_id=NEW.id)
BEGIN SELECT RAISE(ABORT,'Attempt pointer ownership mismatch'); END;
CREATE TRIGGER inserted_active_attempt_owner BEFORE INSERT ON submission_files
WHEN NEW.active_scan_attempt_id IS NOT NULL
BEGIN SELECT RAISE(ABORT,'New revision must have no attempt pointer'); END;
CREATE TRIGGER immutable_file_identity BEFORE UPDATE OF submission_id,revision_number,storage_key,original_name,sha256,size_bytes,mime_type,created_at ON submission_files
BEGIN SELECT RAISE(ABORT,'Immutable file revision'); END;
CREATE TRIGGER rescan_actor_required BEFORE INSERT ON scan_attempts
WHEN NEW.origin='teacher_rescan' AND NEW.requested_by IS NULL
BEGIN SELECT RAISE(ABORT,'Rescan actor required'); END;
CREATE TRIGGER immutable_attempt_identity BEFORE UPDATE OF submission_file_id,origin,idempotency_key,contract_version ON scan_attempts
BEGIN SELECT RAISE(ABORT,'Immutable attempt identity'); END;
CREATE TRIGGER immutable_attempt_actor BEFORE UPDATE OF requested_by ON scan_attempts
WHEN NEW.requested_by IS NOT NULL
BEGIN SELECT RAISE(ABORT,'Immutable attempt actor'); END;
SQL);
};
