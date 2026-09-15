# Qismat Deployment Log

## Environments

- Local/dev: pending
- Staging cPanel: pending
- Production cPanel: pending
- Android CI: foundation build verified
- cPanel preflight: SSH, server runtime, API path and database authentication verified
- iOS Xcode Cloud: pending

## Deployments

No staging or production deployments yet.

| Date | Environment | Component | Version/build | Commit | Migration version | Result | Rollback |
|---|---|---|---|---|---|---|---|
| 2026-09-15 | GitHub Actions | Android | shell 001 | `afcc31a` | N/A | Passed; APK artifact produced by run `34925738195` | Not applicable; CI artifact only |
| 2026-09-15 | cPanel preflight | Server/database | 0.1.0-dev | `453afd6` | `2026_09_15_043815` planned, not run | Failed before server login: encrypted SSH key requires an unavailable passphrase (run `34931693576`) | No server changes occurred; no rollback required |
| 2026-09-15 | cPanel preflight | SSH/runtime | 0.1.0-dev | `0f68c54` | Not run | Passed SSH authentication; PHP 8.2.33 confirmed, server Composer unavailable (run `34939195446`) | No deployment occurred |
| 2026-09-15 | cPanel preflight | Database | 0.1.0-dev | `be1fe5f` | Not run | MariaDB reached but rejected the configured user at localhost (run `34939737992`) | Temporary credential file removed; no database changes occurred |
| 2026-09-15 | cPanel preflight | Server/database | 0.1.0-dev | `05b12e4` | Not run | Passed SSH, PHP 8.2, path permission and MariaDB `SELECT 1` checks (run `34941862520`) | No deployment occurred |

## Deployment record format

| Date | Environment | Component | Version/build | Commit | Migration version | Result | Rollback |
|---|---|---|---|---|---|---|

## Production rule

Every production deployment must record the deployed commit SHA, application version/build, migration state, result and rollback notes when applicable.

## Required backend secrets

- `CPANEL_API_PATH` — Laravel release/application path outside the public web root where possible
- `FIREBASE_PROJECT_ID` — Firebase project used to validate token audience and issuer
- `FIREBASE_SERVICE_ACCOUNT_JSON` — service-account JSON stored as a GitHub secret and written during deployment to a protected file outside the public web root
- `LARAVEL_APP_KEY` — persistent Laravel encryption key; it must be generated once and retained across deployments
- `CPANEL_SSH_KEY_PASSPHRASE` — passphrase for the encrypted deployment key

The existing `DATABASE_NAME`, `DATABASE_USER` and `DATABASE_PASSWORD` secrets map to Laravel's `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` server environment values. Deployment merges only its managed settings into the server `.env` file and preserves unrelated settings.
