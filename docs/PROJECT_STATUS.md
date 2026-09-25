# Qismat Project Status

Last updated: 2026-09-20

## Overall

**Phase:** Identity and profile foundation
**Overall progress:** 35%
**Current release:** 0.1.0-dev

| Area | Status | Progress |
|---|---|---:|
| Architecture | In progress | 70% |
| Backend/API | In progress | 60% |
| Website | In progress | 40% |
| Admin dashboard | In progress | 20% |
| Flutter mobile app | In progress | 20% |
| Android CI/CD | In progress | 60% |
| iOS/Xcode Cloud | Planned | 5% |
| cPanel deployment | Staging operational | 75% |
| QA/security | In progress | 25% |

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
- [x] Profile onboarding, moderation state and explicit discovery opt-in API
- [x] Shared API gateway at `admin.qismatconnections.com/api/v1`
- [x] Successful cPanel staging deployment with database migrations and HTTPS health verification
- [x] Web, admin, backend and Android checks passing for the deployed release

## In progress / next

- [x] Firebase project/service-account configuration
- [ ] Complete remaining admin and Android client integration
- [x] Member web authentication and profile onboarding integration
- [ ] Profile photos and privacy rules
- [ ] Partner preferences and recommendation engine
- [ ] Search/filter APIs
- [ ] Interests, favourites and profile views
- [ ] Conversations/messages after mutual acceptance
- [ ] Block/report/moderation APIs
- [ ] Connect web/admin/mobile shells to API
- [ ] Admin authentication and profile moderation APIs/UI
- [ ] Pin the cPanel SSH host key in GitHub Secrets
- [ ] Add atomic releases and a tested staging rollback procedure
- [ ] Xcode Cloud configuration after web, admin and Android stabilization

## Build identifiers

| Component | Version/build |
|---|---|
| Platform | 0.1.0-dev |
| Backend | Laravel 12 / API foundation 002 |
| Web | shell 001 |
| Admin | shell 001 |
| Android | shell 001 |
| iOS | shell 001 |
| Database schema | through migration `2026_09_20_000100` |

## Major blockers and risks

- cPanel SSH, PHP 8.2, MariaDB client and Git are verified. Server Composer and Node.js are unavailable, so deployable artifacts are built in GitHub Actions.
- MariaDB authentication and database access are verified by the cPanel preflight.
- `CPANEL_API_PATH`, Firebase credentials and the persistent `LARAVEL_APP_KEY` are configured.
- Staging is deployed and healthy through the admin-domain gateway.
- Client applications remain UI shells; endpoint constants alone do not implement login, profiles, matching or moderation screens.
- The admin panel has no admin authentication or authorization flow yet.
- cPanel deployment currently updates files in place and uses `ssh-keyscan`; atomic release switching and a pinned host key remain outstanding.
- No production migration is authorized until database/storage backup and rollback procedures are completed.
- Apple Developer and payment-provider credentials remain pending for their later stages.

## Delivery priority

The active product phase completes member web and admin first while Android implements the same modules against the shared API. Native iOS implementation follows the stabilized feature set and uses Xcode Cloud for builds and releases.

## Rule

Update this file whenever a meaningful feature, deployment, release, architecture decision or blocker changes project state.
