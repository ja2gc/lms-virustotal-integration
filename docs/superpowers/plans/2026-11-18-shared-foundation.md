# Shared P1a foundation and Member 4 P5 plan

**Goal:** Provide shared persistence contracts for concurrent upload, engine, UI and HTTP work.

**Spec:** SRS FR12/18/20/23/24, data-model.md and integrations.md shared repository contract. The coordinator authorized reassignment and main publication. Keep this existing clone; no worktree or unrelated stream edits.

**Split:** Member 4 implements P1a migrations/bootstrap/provider interface/repository plus P5 HTTP boundaries. Member 1 retains P1b immutable file storage, validated upload service/Student-controller integration, legacy backfill and data/recovery verification. Members 2/3 retain engine/UI. Fixtures prove interoperability only, not real upload/provider completion.

1. Write failing persistence tests for idempotent migration, file/attempt identity, automatic uniqueness, pointer ownership, guarded writes, due claims, crash recovery, rescan replay and context/history projections. Implement SQLite migration runner, CLI-safe bootstrap and repository with injected PDO/clock. Publish a single revision-registration method for Member 1; do not implement their file storage service.
2. Verify concurrent/expired claims, transaction rollback and stale result isolation. Publish exact constructors, row shapes and provider-state schema. Review and publish P1a before consumers connect.
3. Write failing HTTP tests using fixture revisions: status/history object access, POST/CSRF rescan/replay, private-download containment and direct-path denial. Implement P5 through P1a methods only; no parallel controller SQL. Keep template/link integration with Member 3.
4. Run full localhost PHP checks, syntax/JS checks, native preview for available real paths, and independent read-only review. Record unavailable real-upload/live-worker acceptance and P1b/P3 dependencies explicitly.

**Review focus:** cross-object pointers; duplicate rescan token on another actor/submission; claim competition and uncertain starts; stale revision writes; file path traversal/symlinks and public legacy bypass. SQLite/PHP 8.3 is the tested baseline. Do not apply migrations or move files in the user's local database during development.
