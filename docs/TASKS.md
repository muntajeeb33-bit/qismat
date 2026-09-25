# Qismat Task Tracker

Execution order and acceptance criteria are defined in `PRODUCTION_PLAN.md`; this file remains the compact completion checklist.

## In progress
- [x] Add Firebase project/service-account configuration
- [x] Deploy web Firebase Authentication
- [x] Add Google sign-in UI and Firebase-to-Laravel token exchange
- [ ] Enable Google provider in Firebase Console and complete live login QA
- [ ] Connect Android Firebase Authentication client
- [x] Deploy admin authentication and moderation UI
- [ ] Provision and verify the first production administrator account

## Deferred
- [ ] Apple login after Apple Developer credentials are available
- [ ] Branded verification/password-reset email delivery after Firebase template access is restored or custom SMTP is approved

## Delivery order
- [ ] Complete member web modules
- [ ] Complete admin modules and moderation operations
- [ ] Complete Android modules against the shared API
- [ ] Stabilize shared behavior and complete cross-platform QA
- [ ] Build and release the native iOS client through Xcode Cloud

## Next
- [ ] Android Firebase registration/login/verification/password recovery
- [x] Profile CRUD and onboarding-state APIs
- [ ] Photo upload/storage rules
- [ ] Partner preference model
- [ ] Search/filter API
- [x] Interest send/respond API foundation
- [ ] Shortlist/favourites
- [ ] Matching/recommendation service
- [ ] Chat foundation
- [ ] Block/report/privacy
- [x] Admin moderation foundation
- [ ] Membership/payment foundation
- [ ] Notifications

## DevOps
- [x] `.env.example` files without secrets
- [x] GitHub Actions Android build
- [x] Backend/web CI checks
- [x] cPanel deploy workflow with CI-built Laravel dependencies
- [x] cPanel SSH/database preflight workflow
- [x] staging configuration and successful deployment
- [ ] Pin cPanel SSH host key and add atomic release rollback
- [ ] production configuration
- [ ] Xcode Cloud setup after web, admin and Android stabilization

## Completed
- [x] Confirm `shihan84/qismat` repository access
- [x] Initialize repository
- [x] Create foundation branch
- [x] Add project status tracking
- [x] Add roadmap
- [x] Finalize initial architecture ADRs
- [x] Create Laravel 12 backend skeleton
- [x] Convert schema v1 to runnable Laravel migrations
- [x] Add Sanctum authentication bootstrap
- [x] Add API response/CORS baseline
- [x] Add backend API tests and CI workflow
- [x] Create Flutter app shell
- [x] Create web/member portal shell
- [x] Create admin dashboard shell
- [x] Confirm Android CI green after BUG-001
- [x] Add Laravel Firebase ID-token verification middleware
- [x] Add Firebase-to-Sanctum token exchange API
- [x] Require Firebase-verified email before local account synchronization
- [x] Route the shared API through `admin.qismatconnections.com/api/v1`
- [x] Deploy and smoke-test cPanel staging
- [x] Implement web registration, verification, login, recovery and Laravel session exchange
- [x] Implement admin login, role enforcement, moderation queue and approve/reject audit trail
- [x] Implement member web profile editing, completion, moderation submission and discovery controls
- [x] Return admin rejection feedback to the member without exposing it in discovery results
- [x] Proxy email/password Firebase operations through Laravel
- [x] Allow both apex and www member origins through Laravel CORS
- [x] Add Google sign-in to the member website
- [x] Hide Apple sign-in until its provider credentials are available
