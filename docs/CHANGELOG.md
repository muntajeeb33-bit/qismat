# Changelog

All notable changes to Qismat will be recorded here.

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
- Signed Gmail email-verification and resend endpoints
- Verified-email enforcement for member profile, discovery and interaction APIs
- Non-deploying cPanel server/database preflight workflow

### Changed

- Existing auth, profile, match and interest endpoints now use the common API response envelope.
- Duplicate interest attempts return the existing record without duplicating activity history.
- Backend status advanced from a source overlay to a runnable Laravel application.
- Registration now requires email while mobile number remains optional; Firebase/mobile OTP verification is out of scope.

### Planned
- Authentication and verification APIs
- Flutter mobile skeleton
- Web/member portal shell
- Admin dashboard shell
- CI/CD workflows
