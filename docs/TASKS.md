# Qismat Task Tracker

## In progress
- [x] Add Firebase project/service-account configuration
- [ ] Connect web and Android Firebase Authentication clients
- [ ] Connect admin authentication and moderation UI to Laravel

## Delivery order
- [ ] Complete member web modules
- [ ] Complete admin modules and moderation operations
- [ ] Complete Android modules against the shared API
- [ ] Stabilize shared behavior and complete cross-platform QA
- [ ] Build and release the native iOS client through Xcode Cloud

## Next
- [ ] Firebase client registration/login/verification/password recovery
- [x] Profile CRUD and onboarding-state APIs
- [ ] Photo upload/storage rules
- [ ] Partner preference model
- [ ] Search/filter API
- [x] Interest send/respond API foundation
- [ ] Shortlist/favourites
- [ ] Matching/recommendation service
- [ ] Chat foundation
- [ ] Block/report/privacy
- [ ] Admin moderation
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
