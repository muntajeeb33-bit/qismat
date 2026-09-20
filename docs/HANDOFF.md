# Qismat Engineering Handoff

Last updated: 2026-09-20

## Current state

- Upstream repository: `shihan84/qismat`
- Working fork: `muntajeeb33-bit/qismat`
- Active integration branch: `main`
- Last verified staging release: `dea4182`
- Staging deployment: GitHub Actions run `35520740580` passed
- Member site: `https://qismatconnections.com`
- Admin site: `https://admin.qismatconnections.com`
- Shared API: `https://admin.qismatconnections.com/api/v1`
- Health endpoint: `https://admin.qismatconnections.com/api/v1/health`

The Laravel runtime is deployed to `CPANEL_API_PATH`, outside the admin document root. The admin deployment installs `_qismat_api.php` as a symlink to Laravel's public front controller. `admin/public/.htaccess` routes `/api/*` through that entry point and sends other non-file requests to the React admin application.

## Verified behavior

- The Laravel suite passes with 21 tests and 82 assertions; backend CI, web/admin builds and Android build are green.
- Web and admin dependency audits report no known vulnerabilities.
- Admin and member sites return HTTP 200 over valid TLS.
- The shared health endpoint returns the expected `qismat-api` JSON response.
- CORS permits the member origin with credentials.
- Android analysis, tests and release APK builds pass with the shared API URL.
- No private keys, service-account files or production credentials are committed.

## Implemented product foundation

- Firebase ID-token verification and Firebase-to-Sanctum exchange.
- Verified-email and active-account enforcement.
- Profile CRUD, adult eligibility, moderation state, submission and discovery opt-in.
- Discovery excludes hidden, unapproved, opted-out, inactive and unverified accounts.
- Interest send/respond foundation; new interests require a discoverable receiver.
- Activity logging for interest actions.
- Versioned API response and error contract.
- cPanel database preflight, environment merging, migrations, frontend deployment and health verification.

## Material gaps

1. Web, admin and Flutter are still visual shells. API base URLs are configured, but login, onboarding, discovery and moderation screens do not yet call the API.
2. Admin authentication, role middleware, moderation endpoints and audit UI are not implemented. Never treat the current static admin page as an authorization boundary.
3. Photo upload/moderation, partner preferences, favourites, reports, blocking, chat, notifications, subscriptions and payments remain incomplete.
4. The iOS native project is not committed and Xcode Cloud is not active.
5. Deployment updates files in place. Atomic release directories, rollback switching and a tested database rollback procedure are still required.
6. SSH host discovery currently uses `ssh-keyscan`. Replace it with a pinned `known_hosts` value stored in GitHub Secrets.
7. Remove or redirect the obsolete `api.qismatconnections.com` DNS/subdomain after confirming no external client uses it.

## Agreed delivery order

Complete the member web application and admin dashboard first. Develop the Android application against the same shared API and module contracts during this phase. Start native iOS packaging after the web, admin and Android feature set is stable; build and release iOS through Xcode Cloud.

1. Add admin Firebase login, enforce Laravel `role=admin` middleware and build moderation list/approve/reject endpoints and screens.
2. Connect member web and Android Firebase flows to `POST /api/v1/auth/firebase`; store Sanctum tokens securely and implement logout and recovery.
3. Build profile onboarding on web and Android against the current profile/status endpoints, with admin moderation support.
4. Add photo storage, moderation and visibility rules across web, admin and Android.
5. Connect discovery and interests, then implement favourites, reporting/blocking and mutual-match chat across web and Android.
6. Complete responsive web/admin QA and Android release QA against the shared staging API.
7. Commit the iOS native project, configure Firebase iOS files through secure build settings, reuse the stable API/module behavior and enable Xcode Cloud.
8. Add atomic cPanel releases, pinned SSH host verification, backup/restore validation and a documented production release gate.

## Deployment and rollback notes

Relevant upstream `main` changes automatically run `.github/workflows/deploy-cpanel.yml`; documentation-only and mobile-only commits do not deploy cPanel. Deployments are serialized. The workflow builds artifacts in GitHub Actions, verifies the database, uploads Laravel and both frontends, runs migrations, installs the admin-domain gateway and validates the exact JSON health response.

For application rollback, redeploy a known-good Git ref through the manual workflow input. Do not reverse database migrations automatically. Review each migration, restore from a tested backup when required, and record the result in `docs/DEPLOYMENT_LOG.md`.

Required GitHub secrets are `CPANEL_HOST`, `CPANEL_USER`, `CPANEL_PORT`, `CPANEL_SSH_KEY`, `CPANEL_SSH_KEY_PASSPHRASE`, `CPANEL_API_PATH`, `CPANEL_WEB_PATH`, `CPANEL_ADMIN_PATH`, `DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWORD`, `FIREBASE_PROJECT_ID`, `FIREBASE_SERVICE_ACCOUNT_JSON` and `LARAVEL_APP_KEY`. Values must never be copied into repository files or handoff messages.
