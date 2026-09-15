# Qismat Decision Log

## ADR-001 — Monorepo
**Status:** Accepted
**Date:** 2026-09-15

Use one GitHub repository (`shihan84/qismat`) for backend, web, admin, mobile, deployment and project documentation.

## ADR-002 — API-first architecture
**Status:** Accepted
**Date:** 2026-09-15

Use one backend/API as the source of business logic for website, admin, Android and iOS clients.

## ADR-003 — Laravel 12 baseline
**Status:** Accepted provisionally
**Date:** 2026-09-15

Use Laravel 12 with PHP 8.2+ as the initial backend baseline because it is more likely to be compatible with cPanel environments. Reassess after confirming the hosting PHP version.

## ADR-004 — Flutter mobile
**Status:** Accepted
**Date:** 2026-09-15

Use one Flutter codebase for Android and iOS to reduce duplicate implementation while retaining native packaging and release pipelines.

## ADR-005 — Build/release split
**Status:** Accepted
**Date:** 2026-09-15

Android builds run in GitHub Actions. iOS builds run in Xcode Cloud.

## ADR-006 — Production hosting
**Status:** Accepted
**Date:** 2026-09-15

Use the user's cPanel hosting with SSH access for backend, web and admin deployment, with MySQL/MariaDB as the production database.

## ADR-007 — Tracking is part of the repository
**Status:** Accepted
**Date:** 2026-09-15

Maintain project status, roadmap, tasks, changelog, bugs, decisions and deployment history in version control.

## ADR-008 — Gmail email verification
**Status:** Superseded by ADR-009
**Date:** 2026-09-15

Use Laravel signed email-verification links delivered through Gmail SMTP for account verification. Email is required at registration; mobile number remains optional. Firebase Authentication and mobile OTP verification are not part of the current account-verification flow. Firebase may still be evaluated separately for future push notifications.

## ADR-009 — Firebase Authentication with Laravel token exchange
**Status:** Accepted
**Date:** 2026-09-15

Use Firebase Authentication on web/mobile for email/password registration, login, verification emails and password recovery. Clients send Firebase ID tokens to the Laravel API. Laravel verifies the token server-side, synchronizes the Qismat user by Firebase UID and issues a Sanctum token for Qismat API authorization. Business data and authorization remain in Laravel/MySQL.
