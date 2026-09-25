# Qismat Matrimony Platform

Qismat is a full-stack matrimonial platform consisting of:

- Public matrimonial website and member portal
- Admin and moderation dashboard
- Laravel API/backend for cPanel + SSH hosting
- Flutter mobile application for Android and iOS
- Android CI/CD using GitHub Actions
- iOS CI/CD using Xcode Cloud
- Project progress, release, deployment, decision and bug tracking

## Planned repository layout

```text
qismat/
├── backend/                 # Laravel API + web backend
├── web/                     # Matrimonial website/member portal
├── admin/                   # Admin dashboard
├── mobile/                  # Flutter Android/iOS app
├── docs/                    # Project tracking and technical docs
├── deployment/              # cPanel/server deployment assets
└── .github/workflows/       # CI/CD
```

## Core product scope

Authentication and verification, matrimonial profiles, photos, family/education/career details, partner preferences, advanced search, recommendations, interests, acceptance/rejection, shortlist, mutual-match chat, privacy controls, profile verification, block/report, premium plans, payments-ready architecture, notifications, admin moderation, analytics and audit logs.

## Hosting targets

- Member website: `https://qismatconnections.com`
- Admin panel and shared API gateway: `https://admin.qismatconnections.com`
- Shared API base URL for web, admin, Android and iOS: `https://admin.qismatconnections.com/api/v1`
- Laravel runtime: private cPanel path deployed over SSH and exposed only through the admin gateway
- Database: MySQL/MariaDB
- Android builds: GitHub Actions
- iOS builds: Xcode Cloud

## Project tracking

New contributors should start with `docs/HANDOFF.md`. The end-to-end production sequence and launch gates live in `docs/PRODUCTION_PLAN.md`. Project tracking continues in `docs/PROJECT_STATUS.md`, `docs/ROADMAP.md`, `docs/TASKS.md`, `docs/CHANGELOG.md`, `docs/DECISIONS.md`, `docs/BUGS.md`, and `docs/DEPLOYMENT_LOG.md`.

## Security

Never commit production secrets, API keys, private certificates, signing files, database passwords or service-account credentials. Use environment variables and repository/server secret stores.
