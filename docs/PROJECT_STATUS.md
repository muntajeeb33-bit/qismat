# Qismat Project Status

Last updated: 2026-09-15

## Overall

**Phase:** Foundation implementation
**Overall progress:** 27%
**Current release:** 0.1.0-dev

| Area | Status | Progress |
|---|---|---:|
| Architecture | In progress | 70% |
| Backend/API | In progress | 50% |
| Website | In progress | 20% |
| Admin dashboard | In progress | 20% |
| Flutter mobile app | In progress | 20% |
| Android CI/CD | In progress | 40% |
| iOS/Xcode Cloud | Planned | 5% |
| cPanel deployment | In progress | 12% |
| QA/security | In progress | 15% |

## Completed in current foundation batch

- [x] GitHub repository verified and initialized
- [x] cPanel + SSH production target recorded
- [x] Android build target: GitHub Actions
- [x] iOS build target: Xcode Cloud
- [x] Architecture decision records and roadmap
- [x] Backend API/domain overlay started
- [x] MySQL/MariaDB reference schema v1
- [x] Initial authentication and profile controller/model code
- [x] Activity and admin audit tracking schema
- [x] Flutter application shell
- [x] Public web shell
- [x] Admin dashboard shell
- [x] Android GitHub Actions workflow
- [x] Web/admin GitHub Actions workflow
- [x] Full Laravel 12 application skeleton and Artisan bootstrap
- [x] Laravel Sanctum token authentication bootstrap
- [x] API response contract and CORS configuration
- [x] Backend migrations, factories, plan seed data and API test suite
- [x] Backend GitHub Actions workflow
- [x] Latest Android CI result confirmed green after BUG-001
- [x] Firebase ID-token verification middleware and Sanctum exchange API
- [x] Firebase-verified-email enforcement before user synchronization
- [x] cPanel SSH/database preflight workflow

## In progress / next

- [ ] Firebase project/service-account configuration
- [ ] Web and Flutter Firebase client integration
- [ ] Profile photos and privacy rules
- [ ] Partner preferences and recommendation engine
- [ ] Search/filter APIs
- [ ] Interests, favourites and profile views
- [ ] Conversations/messages after mutual acceptance
- [ ] Block/report/moderation APIs
- [ ] Connect web/admin/mobile shells to API
- [ ] cPanel staging deployment
- [ ] Xcode Cloud configuration

## Build identifiers

| Component | Version/build |
|---|---|
| Platform | 0.1.0-dev |
| Backend | Laravel 12 / API foundation 002 |
| Web | shell 001 |
| Admin | shell 001 |
| Android | shell 001 |
| iOS | shell 001 |
| Database schema | migration `2026_09_15_000400` plus Sanctum migration `2026_09_15_043815` |

## Major blockers

- cPanel SSH, PHP 8.2, MariaDB client and Git are verified. Server Composer and Node.js are unavailable, so deployable artifacts are built in GitHub Actions.
- MariaDB authentication and database access are verified by the cPanel preflight.
- `CPANEL_API_PATH`, Firebase credentials and the persistent `LARAVEL_APP_KEY` are configured.
- First staging deployment awaits merge of PR #1 because GitHub only dispatches manual workflows that exist on the default branch.
- No production migration is authorized until database/storage backup and rollback procedures are completed.
- Apple Developer and payment-provider credentials remain pending for their later stages.

## Rule

Update this file whenever a meaningful feature, deployment, release, architecture decision or blocker changes project state.
