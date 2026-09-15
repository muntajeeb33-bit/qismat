# Qismat Bug Tracker

Use this file for compact repository-level bug tracking. Larger bugs should also have GitHub issues.

## Open

### DEPLOY-002 — cPanel database credentials are rejected
- Date found: 2026-09-15
- Component: cPanel MariaDB / GitHub Actions
- Severity: deployment-blocking
- Environment/build: cPanel Preflight run `34939737992`
- Reproduction: run the cPanel preflight with the configured `DATABASE_NAME`, `DATABASE_USER` and `DATABASE_PASSWORD` secrets.
- Root cause: MariaDB rejects the configured database user at `localhost`; the password may not match, the cPanel-prefixed name may be incomplete, or the user may not be assigned to the database.
- Fix: verify the full cPanel database/user names, reset the database-user password if necessary, assign that user to the database with required privileges, and update the matching GitHub secrets.
- Commit/PR: PR #1
- Resolution status: Open; migrations and first backend deployment remain gated.

## Resolved

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
