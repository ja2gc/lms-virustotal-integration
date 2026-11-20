# Verification records

These small sanitized records support the validation guide; the application does not require them at runtime.

- `validation/`: integrated check summary and two representative browser screenshots.
- `performance/`, `reliability/`, `live/`: measured application load, deterministic fault trials and limited synthetic provider exchange.
- `uat/`: recorded manual acceptance scope.
- Other folders: scoped historical implementation checks retained for traceability.

Reproducible runners/fixtures live in `tests/`. The published [system demo (MP4 download)](https://github.com/ja2gc/lms-virustotal-integration/raw/refs/heads/main/docs/demo/lmstotal-final-demo.mp4) is a presentation artifact, separate from verification results. Generated diagnostics, rehearsal recordings, databases, sessions, uploads and private backups stay in ignored local folders. Superseded startup logs/screenshots were archived locally under `tmp/`; Git history preserves prior tracked versions. Never add API keys, real student work or raw provider bodies.
