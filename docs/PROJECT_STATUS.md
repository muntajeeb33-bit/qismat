# Qismat Project Status

Last updated: 2026-09-15

## Overall

**Phase:** Foundation implementation
**Overall progress:** 15%
**Current release:** 0.1.0-dev

| Area | Status | Progress |
|---|---|---:|
| Architecture | In progress | 70% |
| Backend/API | In progress | 20% |
| Website | In progress | 20% |
| Admin dashboard | In progress | 20% |
| Flutter mobile app | In progress | 20% |
| Android CI/CD | In progress | 30% |
| iOS/Xcode Cloud | Planned | 5% |
| cPanel deployment | Planned | 5% |
| QA/security | Planned | 5% |

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

## In progress / next

- [ ] Full Laravel 12 framework skeleton and Sanctum bootstrap
- [ ] Convert reference schema to Laravel migrations
- [ ] OTP/email verification
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
| Backend | source overlay 001 |
| Web | shell 001 |
| Admin | shell 001 |
| Android | shell 001 |
| iOS | shell 001 |
| Database schema | mysql-v1 |

## Rule

Update this file whenever a meaningful feature, deployment, release, architecture decision or blocker changes project state.
