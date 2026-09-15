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
