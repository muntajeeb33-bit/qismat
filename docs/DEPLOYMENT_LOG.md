# Qismat Deployment Log

## Environments

- Local/dev: pending
- Staging cPanel: pending
- Production cPanel: pending
- Android CI: foundation build verified
- cPanel preflight: workflow prepared; result pending
- iOS Xcode Cloud: pending

## Deployments

No staging or production deployments yet.

| Date | Environment | Component | Version/build | Commit | Migration version | Result | Rollback |
|---|---|---|---|---|---|---|---|
| 2026-09-15 | GitHub Actions | Android | shell 001 | `afcc31a` | N/A | Passed; APK artifact produced by run `34925738195` | Not applicable; CI artifact only |
| 2026-09-15 | cPanel preflight | Server/database | 0.1.0-dev | `453afd6` | `2026_09_15_043815` planned, not run | Failed before server login: encrypted SSH key requires an unavailable passphrase (run `34931693576`) | No server changes occurred; no rollback required |

## Deployment record format

| Date | Environment | Component | Version/build | Commit | Migration version | Result | Rollback |
|---|---|---|---|---|---|---|

## Production rule

Every production deployment must record the deployed commit SHA, application version/build, migration state, result and rollback notes when applicable.

## Required backend secrets

- `CPANEL_API_PATH` — Laravel release/application path outside the public web root where possible
- `FIREBASE_PROJECT_ID` — Firebase project used to validate token audience and issuer
- `FIREBASE_SERVICE_ACCOUNT_JSON` — service-account JSON stored as a GitHub secret and written during deployment to a protected file outside the public web root

The existing `DATABASE_NAME`, `DATABASE_USER` and `DATABASE_PASSWORD` secrets map to Laravel's `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` server environment values. Deployment must update only these named settings and must not replace the complete server `.env` file.
