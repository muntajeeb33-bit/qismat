# Qismat Bug Tracker

Use this file for compact repository-level bug tracking. Larger bugs should also have GitHub issues.

## Open

### DEPLOY-003 — MariaDB rejects default indexed string length
- Date found: 2026-09-15
- Component: Laravel migrations / cPanel MariaDB
- Severity: deployment-blocking
- Environment/build: staging deployment run `34942870359`
- Reproduction: run the initial Laravel migrations against the cPanel MariaDB database.
- Root cause: the server permits a maximum 1000-byte index, while Laravel's default 255-character `utf8mb4` indexed strings can require 1020 bytes.
- Fix: use Laravel's 191-character default string length and allow the initial migration to resume safely around tables created before the failed DDL statement.
- Commit/PR: pending
- Resolution status: Fix implemented; staging verification pending.

## Resolved

### DEPLOY-002 — cPanel database credentials are rejected
- Date found: 2026-09-15
- Component: cPanel MariaDB / GitHub Actions
- Severity: deployment-blocking
- Environment/build: cPanel Preflight runs `34939737992` and `34941862520`
- Root cause: MariaDB rejected the originally configured database credentials at `localhost`.
- Fix: update the full cPanel-prefixed database name/user/password and safely quote special characters in the generated client option file.
- Fix commit: `05b12e4`
- Date resolved: 2026-09-15
- Verification: preflight run `34941862520` completed successfully, including `SELECT 1` against the configured database.
- Resolution status: Resolved and verified.

### DEPLOY-001 — cPanel SSH deploy key requires a passphrase
- Date found: 2026-09-15
- Component: cPanel deployment / GitHub Actions
- Severity: deployment-blocking
- Environment/build: cPanel Preflight runs `34931571866`, `34931693576` and `34939195446`
- Root cause: the stored private key was encrypted and the workflow had no non-interactive passphrase support.
- Fix: add `CPANEL_SSH_KEY_PASSPHRASE`, unlock the temporary runner copy of the key, and authorize its public half in cPanel.
- Fix commit: `0f68c54`
- Date resolved: 2026-09-15
- Verification: run `34939195446` authenticated over SSH and reported PHP 8.2.33 from the cPanel host.
- Resolution status: Resolved and verified.

### BUG-001 — Android CI Flutter analyze failure
- Date found: 2026-09-15
- Component: Flutter / Android CI
- Severity: build-blocking
- Environment/build: GitHub Actions, Flutter stable 3.47.4
- Reproduction: CI generated Flutter's default `test/widget_test.dart`, which referenced the default `MyApp` class.
- Root cause: Qismat's root widget is `QismatApp`, so the generated default smoke test did not match the committed application.
- Fix: commit a Qismat-specific widget smoke test before native project generation.
- Fix commit: `c115e611df77f7d01130f9289b7fe4bd0c54503a`
- Date resolved: 2026-09-15
- Verification: latest Android workflow run `34925738195` completed successfully for commit `afcc31a`.
- Resolution status: Resolved and verified.

## Bug record format

- ID
- Date found
- Component
- Severity
- Environment/build
- Reproduction
- Root cause
- Fix
- Commit/PR
- Date resolved
