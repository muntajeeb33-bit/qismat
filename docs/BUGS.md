# Qismat Bug Tracker

Use this file for compact repository-level bug tracking. Larger bugs should also have GitHub issues.

## Open

### DEPLOY-001 — cPanel SSH deploy key requires a passphrase
- Date found: 2026-09-15
- Component: cPanel deployment / GitHub Actions
- Severity: deployment-blocking
- Environment/build: cPanel Preflight runs `34931571866` and `34931693576`
- Reproduction: run the cPanel preflight with the configured `CPANEL_SSH_KEY` secret.
- Root cause: the stored private key is encrypted and GitHub Actions has no non-interactive passphrase with which to unlock it. The host is reachable and the required SSH/database secret names are populated.
- Fix: replace the secret with a dedicated non-interactive deployment private key whose public key is authorized for `CPANEL_USER`, or explicitly design and configure passphrase-secret support.
- Commit/PR: PR #1; diagnostic workflow commit `453afd6`
- Resolution status: Open; server and database checks are blocked before authentication.

## Resolved

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
