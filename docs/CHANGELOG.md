# Changelog

## Unreleased

- Added Firebase email registration, verification, login and password recovery to the member website.
- Added Firebase admin login with server-enforced active-admin authorization.
- Added admin dashboard counts, a pending-profile moderation queue and audited approve/reject decisions.
- Added the `qismat:admin` console command for controlled administrator provisioning and session revocation.
- Added Firebase client configuration to the staging deployment workflow.

All notable changes to Qismat will be recorded here.

## [Unreleased] - 2026-09-20

### Added
- Profile onboarding, moderation state and explicit discovery opt-in APIs.
- Shared API gateway at `https://admin.qismatconnections.com/api/v1`.
- Client endpoint configuration for web, admin, Android and iOS.
- Automatic cPanel staging deployment for relevant upstream `main` changes.

### Fixed
- Corrected active-member defaults in backend factories.
- Prevented direct interests from bypassing profile discovery eligibility.
- Cleared stale submission timestamps when reviewed profile data changes.
- Validated match age and demographic filters.
- Serialized staging deployments and made the health check validate the expected JSON service response.
- Avoided the invalid `api.qismatconnections.com` TLS certificate.

## [0.1.0-dev] - 2026-09-15

### Added
- Repository initialization
- Project scope and hosting targets
- Foundation branch
- Project status tracker
- Roadmap
- Task tracker
- Architecture document
- Architecture decision log
- Bootable Laravel 12 framework application and Artisan entry point
- Laravel Sanctum personal access token support
- Standard API success/error response baseline and CORS configuration
- Current-user API and inactive-account login enforcement
- Public `QSM` matrimonial profile identifiers generated independently of database IDs
- Reproducible migrations, membership plan seed data and model factory
- API tests for health, registration, login, authentication, profile management and interest rules
- Backend PHP 8.2 GitHub Actions workflow
- Locked web/admin dependency graphs and deterministic `npm ci` builds
- Firebase ID-token verification middleware and Firebase-to-Sanctum exchange endpoint
- Firebase UID mapping and verified-email enforcement for local Qismat accounts
- Non-deploying cPanel server/database preflight workflow
- cPanel deployment workflow with CI-built Composer dependencies, protected Firebase credential upload, managed `.env` merging, migrations and API health verification

### Changed

- Existing auth, profile, match and interest endpoints now use the common API response envelope.
- Duplicate interest attempts return the existing record without duplicating activity history.
- Backend status advanced from a source overlay to a runnable Laravel application.
- Authentication ownership moved from Laravel password/Gmail SMTP flows to Firebase Authentication; Laravel remains the API authorization and business-data authority.
- Encrypted cPanel SSH deployment keys are supported through a separate passphrase secret.

### Planned
- Authentication and verification APIs
- Flutter mobile skeleton
- Web/member portal shell
- Admin dashboard shell
- CI/CD workflows
