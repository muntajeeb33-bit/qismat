# Qismat Bug Tracker

Use this file for compact repository-level bug tracking. Larger bugs should also have GitHub issues.

## Open

No known blocking bugs at this stage.

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
