# Qismat Roadmap

## Current delivery sequence — 2026-09-25

`PRODUCTION_PLAN.md` is the authoritative execution sequence and launch-gate checklist. This roadmap groups the same scope by product capability.

1. Enable Google as a Firebase Authentication provider and complete live web sign-in QA.
2. Provision and verify the first production administrator account.
3. Complete profile photos, privacy controls and partner preferences.
4. Build search, filters, favourites and recommendation foundations.
5. Connect Android authentication and member modules to the shared API.

### Explicitly deferred

- Apple login remains hidden until Apple Developer membership, Service ID, Team ID, Key ID and private key are available.
- Branded authentication email delivery remains on the future plan. Default Firebase emails stay active while Firebase template editing is restricted; a Laravel/custom-SMTP flow is the fallback.
- Native iOS release work follows stabilization of the shared web, admin and Android feature set.

## Phase 0 — Foundation
- Repository structure and standards
- Architecture decisions
- Environment strategy
- cPanel deployment plan
- CI/CD plan
- Project tracking files

## Phase 1 — Identity and Profiles
- Registration/login
- Email/mobile verification
- Google sign-in
- Apple sign-in after Apple Developer configuration
- User roles
- Matrimonial profile creation
- Family, education and career details
- Photo upload and moderation
- Partner preferences
- Profile completeness

## Phase 2 — Discovery and Matching
- Search and filters
- Recommendations
- Compatibility scoring foundation
- Profile views
- Shortlist/favourites
- Send/receive interest
- Accept/decline workflows

## Phase 3 — Communication and Safety
- Mutual-match chat
- Push notifications
- Privacy controls
- Hide/contact/photo permissions
- Block/report
- Verification requests
- Admin moderation queue

## Phase 4 — Membership and Monetization
- Membership plans
- Premium feature gating
- Razorpay-ready payment layer
- Subscription records
- Invoices/payment history
- Promo/coupon readiness

## Phase 5 — Admin and Analytics
- User/profile management
- Verification/moderation
- Reports and abuse management
- Membership/payment management
- CMS/banner management
- Metrics and audit logs

## Phase 6 — Mobile Release Engineering
- Android GitHub Actions build pipeline
- Android signing/release workflow
- iOS Xcode Cloud pipeline
- App Store/Play Store environment separation
- Crash/error monitoring integration

## Phase 7 — Production Launch
- cPanel staging deployment
- Production deployment
- Database backup/restore procedure
- Security review
- Performance/load testing
- Launch checklist
