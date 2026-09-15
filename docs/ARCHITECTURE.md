# Qismat Architecture

## Objectives

Qismat will use one API/backend for web, admin, Android and iOS. The production environment is a cPanel server with SSH access.

## Initial stack

- Backend/API: Laravel 12
- PHP: 8.2+ (8.3 preferred if available on hosting)
- Database: MySQL/MariaDB
- Website/member portal: React-based web frontend
- Admin dashboard: React-based admin frontend
- Mobile: Flutter (single Android/iOS codebase)
- Android CI/CD: GitHub Actions
- iOS CI/CD: Xcode Cloud
- Notifications: Firebase Cloud Messaging + APNs
- Payments: provider abstraction with Razorpay first
- Email: SMTP abstraction; cPanel SMTP supported
- File storage: local cPanel storage first, S3-compatible adapter supported

## Repository boundaries

```text
backend/       Laravel API and domain logic
web/           Public site and member portal
admin/         Admin/moderation dashboard
mobile/        Flutter Android/iOS application
docs/          Product, engineering and progress tracking
deployment/    cPanel and infrastructure scripts/config
.github/       GitHub Actions workflows and templates
```

## API-first rule

Business rules live in the backend. Web, admin and mobile consume versioned APIs. Do not duplicate matching, privacy, subscription, moderation or permission logic across clients.

## Authentication flow

Firebase Authentication handles client-side email/password registration, login, verification email delivery and password recovery. After Firebase authentication, the client sends its ID token to `POST /api/v1/auth/firebase`. Laravel middleware verifies the token and its revocation status, requires a Firebase-verified email, synchronizes the local Qismat user by Firebase UID, and returns a Sanctum token. All matrimonial business authorization remains in Laravel.

## Core domains

1. Identity and authentication
2. Matrimonial profiles
3. Family/education/career details
4. Photos and verification
5. Partner preferences
6. Discovery/search/recommendations
7. Interests and mutual matches
8. Shortlists/profile views
9. Chat and notifications
10. Privacy, blocking and reporting
11. Memberships/payments
12. Administration/moderation
13. Analytics/activity/audit logs

## Deployment model

Suggested endpoints:

- `www.<domain>` — public/member website
- `admin.<domain>` — admin dashboard
- `api.<domain>` — backend API

All secrets stay in server environment variables, GitHub Secrets or Xcode Cloud environment/secrets. No production credentials are committed.

## Server compatibility

Laravel 12 is selected initially because it supports PHP 8.2+, which is safer for cPanel compatibility. Once the server PHP version is verified, upgrading the framework can be evaluated separately.
