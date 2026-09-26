# Qismat Deployment Log

## Environments

- Local/dev: pending
- Staging cPanel: operational
- Production cPanel: pending
- Android CI: foundation build verified
- cPanel preflight: SSH, server runtime, API path and database authentication verified
- iOS Xcode Cloud: pending

## Deployments

Staging is deployed automatically after relevant changes reach upstream `main`.

| Date | Environment | Component | Version/build | Commit | Migration version | Result | Rollback |
|---|---|---|---|---|---|---|---|
| 2026-09-15 | GitHub Actions | Android | shell 001 | `afcc31a` | N/A | Passed; APK artifact produced by run `34925738195` | Not applicable; CI artifact only |
| 2026-09-15 | cPanel preflight | Server/database | 0.1.0-dev | `453afd6` | `2026_09_15_043815` planned, not run | Failed before server login: encrypted SSH key requires an unavailable passphrase (run `34931693576`) | No server changes occurred; no rollback required |
| 2026-09-15 | cPanel preflight | SSH/runtime | 0.1.0-dev | `0f68c54` | Not run | Passed SSH authentication; PHP 8.2.33 confirmed, server Composer unavailable (run `34939195446`) | No deployment occurred |
| 2026-09-15 | cPanel preflight | Database | 0.1.0-dev | `be1fe5f` | Not run | MariaDB reached but rejected the configured user at localhost (run `34939737992`) | Temporary credential file removed; no database changes occurred |
| 2026-09-15 | cPanel preflight | Server/database | 0.1.0-dev | `05b12e4` | Not run | Passed SSH, PHP 8.2, path permission and MariaDB `SELECT 1` checks (run `34941862520`) | No deployment occurred |
| 2026-09-15 | Staging cPanel | Backend | 0.1.0-dev | `8ed8219` | Initial migration started, not recorded | Backend uploaded and protected configuration installed; migration stopped on MariaDB's 1000-byte index limit (run `34942870359`) | No tables dropped; deployment stopped before frontends and health check |
| 2026-09-20 | Staging cPanel | Backend, web, admin | 0.1.0-dev | `015fda1` | Through `2026_09_20_000100` | Passed; shared API gateway, frontends, migrations and HTTPS JSON health verified (run `35520092827`) | Redeploy the preceding known-good commit; database rollback remains manual and must be reviewed before use |
| 2026-09-25 | Staging cPanel | Backend, web, admin, deployment recovery | 0.1.0-dev | `adef809` | Through `2026_09_25_000100` | Passed; pre-migration database/member-file backup, immutable release staging, atomic activation and all public health checks verified (run `36150079604`) | Activate a preceding release through the rollback workflow; database migrations remain forward-only |
| 2026-09-25 | Staging cPanel | Backend profile/preferences API | 0.1.0-dev | `1b55a0f` | Through `2026_09_25_000200` | Passed; backend tests, pre-deployment backup, migration, atomic activation, API health and both frontend checks verified (run `36151790370`) | Activate a preceding application release if needed; review or restore the forward-only preference-table migration separately |
| 2026-09-25 | Staging cPanel | Member web profile/preferences and backend privacy | 0.1.0-dev | `e5a10f1` | Through `2026_09_25_000200` | Passed; backend/web checks, pre-deployment backup, atomic activation, API health and both frontend checks verified (run `36153102186`) | Activate a preceding application release if needed; no new database migration |
| 2026-09-25 | Staging cPanel | Private photo API and storage | 0.1.0-dev | `5ecf755` | Through `2026_09_25_000300` | Passed; GD/Fileinfo runtime check, pre-deployment backup, photo metadata migration, activation and public checks verified (run `36154825198`) | Activate a preceding application release if needed; uploaded files remain in shared private storage |
| 2026-09-25 | Staging cPanel | Member/admin photo workflow and discovery privacy | 0.1.0-dev | `e4f8f71` | Through `2026_09_25_000300` | Passed; backend and frontend checks, backup, activation, API health and both frontend checks verified (run `36157171677`) | Activate a preceding application release if needed; no new database migration |
| 2026-09-26 | Staging cPanel | Discovery, favourites and Qismat branding | 0.1.0-dev | `f9867fb` | Through `2026_09_25_000400` | Passed; discovery-index migration, backend/web/admin/Android checks, backup, activation and public health verification completed (run `36220255195`) | Activate the preceding application release if needed; discovery indexes may remain safely applied |
| 2026-09-26 | Staging cPanel | Interests, blocking and safety reports | 0.1.0-dev | `fb45958` | Through `2026_09_26_000100` | Passed; report-resolution migration, 57 backend tests, frontend checks, backup, atomic activation and public verification completed (run `36223403335`) | Activate the preceding application release if needed; report resolution fields remain forward compatible |
| 2026-09-26 | Staging cPanel | Mutual-match messaging | 0.1.0-dev | `5221623` | Through `2026_09_26_000200` | Passed; accepted-interest conversation backfill, 61 backend tests, frontend checks, backup, atomic activation and public verification completed (run `36223891274`) | Activate the preceding application release if needed; retained conversations are forward compatible and messages remain intact |
| 2026-09-26 | Staging cPanel | Account controls and public policies | 0.1.0-dev | `6fd0147` | Through `2026_09_26_000400` | Passed; 69 backend tests, web/admin builds, notification-preference migration, backup, atomic activation and public health verification completed (run `36245460172`) | Activate the preceding application release if needed; the preference table and deleted-account tombstones are forward compatible |
| 2026-09-26 | Staging cPanel | Administrator member operations | 0.1.0-dev | `9407116` | Through `2026_09_26_000400` | Passed; 72 backend tests, web/admin builds, backup, atomic activation and public health verification completed (run `36246502479`) | Activate the preceding application release if needed; no database migration was added |
| 2026-09-26 | Staging cPanel | Private-photo access and trust indicators | 0.1.0-dev | `3b539be` | Through `2026_09_26_000500` | Passed; 73 backend tests, 417 assertions, web/admin builds, backup, migration, atomic activation and public health verification completed (run `36262208926`) | Activate the preceding application release if needed; the access-request table is forward compatible and can remain applied |
| 2026-09-26 | Staging cPanel | Profile completion guidance | 0.1.0-dev | `343726f` | Through `2026_09_26_000500` | Passed; 74 backend tests, 424 assertions, web/admin builds, backup, atomic activation and public health verification completed (run `36262628706`) | Activate the preceding application release if needed; no database migration was added |

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
