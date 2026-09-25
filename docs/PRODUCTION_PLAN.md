# Qismat Production Plan

Last updated: 2026-09-25

## Goal

Ship a safe, supportable Qismat production service consisting of the member website, administrator dashboard, shared Laravel API and Android application. The first public release supports the full journey from account creation through a moderated profile, discovery, mutual interest, safe messaging and account control. iOS follows the stabilized shared feature set through Xcode Cloud.

## Delivery rules

- Laravel owns all business, privacy, moderation, matching and entitlement rules.
- Web, admin and Android consume the same versioned API contracts.
- Complete and stabilize each API and web/admin feature before its Android screen is considered complete.
- Every merge must leave the relevant builds and tests green and must update project tracking when scope or state changes.
- Staging is the integration environment. Production promotion requires the launch gates in this document.
- Payments and Apple sign-in cannot block a safe free launch unless they are explicitly selected as day-one business requirements.

## Production v1 scope

### Member experience

- Email/password and Google authentication, verified email and password recovery.
- Adult-only, marriage-purpose acknowledgment and truthful-profile requirement.
- Profile, family, education, career and partner-preference editing.
- Moderated photo upload with primary-photo and privacy controls.
- Profile completion, submission, rejection feedback, resubmission and discovery opt-in.
- Discovery, search and filters with privacy-safe profile responses.
- Favourites, profile views and sent/received interests.
- Accept/decline interest and mutual-match state.
- Messaging only after a mutual match.
- Notification preferences and Android push notifications.
- Block, report, hide profile, pause discovery, sign out of all sessions and delete account.
- Privacy policy, terms, community rules, safety guidance, contact/support and account-deletion pages.

### Administration

- Role-protected administrator access with no public role-assignment endpoint.
- Dashboard counts and operational queues.
- Member and profile search with status history.
- Profile and photo moderation with reasons and member feedback.
- Report review, user suspension/reactivation and content removal.
- Verification status management.
- Audit records for every material administrator action.
- Basic operational exports that exclude secrets and unnecessary sensitive data.

### Platform and operations

- Versioned Laravel API at `https://admin.qismatconnections.com/api/v1`.
- Private photo storage and authorization-controlled delivery.
- Rate limiting, input validation, safe file validation and structured application logging.
- Automated database and uploaded-media backups with a tested restore.
- Atomic application releases with a tested rollback and pinned SSH host verification.
- Error/crash monitoring, uptime checks and a support escalation path.
- Signed Android App Bundle, Play App Signing, internal/closed testing and staged production rollout.
- Separate development, staging and production configuration without committed credentials.

## Execution sequence

### Gate 0 — Confirm access and operating decisions

Owner actions:

- Enable Google in Firebase Authentication and complete one live web sign-in.
- Sign in once with the intended administrator email, then provision it through `qismat:admin` and verify approval/rejection.
- Confirm the Google Play developer account, production package name and public support email.
- Approve the privacy policy, terms, community rules and data-retention language before public launch.
- Decide whether paid membership is required on day one and, if so, select the payment provider and provide its credentials through secret storage.

Engineering exit criteria:

- Authentication and administrator smoke tests pass on staging.
- The owner-only dependency list is recorded with no credentials in the repository.

### Batch 1 — Production foundations and recovery

- Add immutable release directories and an atomic current-release switch to cPanel deployment.
- Pin the cPanel SSH host key instead of learning it during deployment.
- Add database and member-upload backup jobs, retention rules and a restore rehearsal.
- Add sanitized structured logs, error monitoring and external health/uptime checks.
- Review CORS, trusted proxies, session/token expiry, Firebase revocation, rate limits and production debug settings.
- Establish API pagination, error and validation contracts for every remaining client.

Exit criteria:

- A known-good release can be restored without reversing an unsafe database migration.
- A database and media restore has been demonstrated on a non-production environment.
- No production secret is present in source, build artifacts, logs or client bundles.

### Batch 2 — Complete profiles, photos and privacy

Backend:

- Normalize family details and partner preferences into validated fields suitable for search.
- Add photo create/list/reorder/delete APIs, one primary photo, size/type/dimension limits and image metadata removal.
- Store originals outside public access and return only authorized variants.
- Apply photo visibility rules: members, accepted interests/matches, private or hidden.
- Require approved profile content and an approved primary photo before discovery.

Web and admin:

- Add profile sections, photo manager, privacy choices and accurate completion guidance.
- Add photo moderation, rejection reasons, preview and member feedback.

Android:

- Establish feature-based app structure, API client, secure token storage, environment configuration and reusable error/loading states.
- Implement Firebase email/password and Google authentication, verification, recovery and session exchange.
- Implement the profile and photo flow after the API contract is stable.

Exit criteria:

- An adult member can register, complete a truthful profile, upload photos, receive moderation feedback, resubmit and deliberately enter discovery on web and Android.
- Unauthorized users cannot retrieve private or unapproved photos.

### Batch 3 — Discovery and matching foundation

- Implement partner-preference CRUD and sensible defaults.
- Add paginated discovery and search with age, location, religion/community, language, marital status, education and occupation filters.
- Enforce approval, opt-in, block and visibility rules in one backend query/service.
- Add deterministic first-pass recommendations with explainable factors and no sensitive internal scoring in responses.
- Add favourites and privacy-aware profile-view recording.
- Build matching web views and admin diagnostics, then the equivalent Android screens.

Exit criteria:

- Web and Android return the same eligible profiles for equivalent requests.
- Blocked, suspended, hidden, incomplete and unapproved profiles never appear.
- Pagination and filter queries remain within agreed performance targets using production-like data.

### Batch 4 — Interests, blocking and reports

- Complete sent/received interest lists, accept/decline/cancel rules and mutual-match creation.
- Prevent duplicate, self-directed, blocked or ineligible interest actions.
- Add block/unblock and report flows with reason categories and optional evidence metadata.
- Immediately hide blocked parties from discovery, interests and communication.
- Build the admin report queue, resolution notes, suspension/reactivation and audit history.
- Deliver the complete workflows on web and Android.

Exit criteria:

- Every member-to-member action is authorized server-side and covered by feature tests.
- A report can move from member submission to an audited administrator resolution.

### Batch 5 — Mutual-match messaging and notifications

- Open a conversation only after accepted mutual interest and close access after a block or suspension.
- Add paginated messages, unread counts, read state and safe deletion behavior.
- Add message length/rate limits and report-from-conversation support.
- Register Android FCM tokens, notification preferences and token cleanup.
- Send notifications for moderation results, new interests, accepted interests and new messages without exposing private content on lock screens by default.
- Start with reliable polling or refresh for chat; introduce realtime transport only when hosting support and measured need justify it.

Exit criteria:

- Non-matches cannot create or read conversations.
- Push notification deep links open the correct authorized Android screen.
- Blocking immediately stops new messages and notifications.

### Batch 6 — Complete administration and support operations

- Add member/profile/report search, filters and full moderation context.
- Add account state changes, discovery removal, photo actions and verification actions with mandatory reasons.
- Add operational metrics for registrations, moderation backlog, reports, matches and active users.
- Add a support workflow for account access, safety reports and deletion requests.
- Confirm least-privilege administrator behavior and session revocation after role/status changes.

Exit criteria:

- Administrators can operate every v1 safety and moderation workflow without database edits.
- Audit logs identify actor, action, target, timestamp and relevant before/after state.

### Batch 7 — Account control, legal pages and compliance

- Add in-app and web account-deletion requests, confirmation, session revocation and documented retention exceptions.
- Add data export or support-assisted access where required by the approved policy.
- Publish privacy policy, terms, community rules, safety guidance, contact and deletion instructions at stable public URLs.
- Add consent/policy version tracking for material member acknowledgments.
- Complete the Google Play Data safety declarations from the actual production data flows.
- Review analytics, logs and notifications for data minimization and retention.

Exit criteria:

- Account deletion is accessible from both Android and the public web and has been tested end to end.
- Store declarations match implemented collection, sharing, encryption and deletion behavior.
- Legal and support URLs work without authentication.

### Batch 8 — Android release engineering and store readiness

- Commit stable Android/iOS platform projects instead of generating them during every CI run.
- Fix the production application ID, display name, icons, splash screen and deep links.
- Target Android 16 / API level 36 or the newer Play requirement applicable at submission.
- Build a signed Android App Bundle with a protected upload key and enroll in Play App Signing.
- Add version/build numbering, changelog generation and separate staging/production Firebase configuration.
- Add Crashlytics or an approved crash monitor with privacy-reviewed diagnostics.
- Test supported screen sizes, accessibility, poor networks, interrupted uploads, token expiry and app upgrades.
- Prepare screenshots, descriptions, content rating, privacy/Data safety forms and reviewer access instructions.

Release progression:

1. CI artifact and device smoke tests.
2. Play internal testing.
3. Closed testing with representative members and administrators.
4. Release candidate with no open critical/high defects.
5. Staged production rollout with monitoring and rollback criteria.

Exit criteria:

- The release AAB is reproducible, signed and installable through Play testing.
- Critical journeys pass on at least one current Android version, one older supported version and representative low/mid-range devices.
- Store review requirements, account deletion and Data safety disclosures are complete.

### Batch 9 — Production cutover

- Freeze schema-changing work and create a release candidate.
- Back up production database and media, verify restore artifacts and record migration state.
- Run security, privacy, performance, accessibility and cross-browser/device acceptance checks.
- Seed only approved plans/configuration and provision production administrators through the console command.
- Deploy web, admin and API atomically; run smoke tests; then promote Android through staged rollout.
- Monitor authentication failures, API errors, queues, storage, moderation backlog and crash-free sessions.

Exit criteria:

- Every launch gate below is signed off.
- Deployment and rollback owners are identified for the launch window.
- The deployed commit, schema version, Android version code and verification results are recorded in `DEPLOYMENT_LOG.md`.

## Launch gates

| Gate | Required evidence |
|---|---|
| Product | All v1 journeys pass on web and Android; no placeholder screens or dead links in a launch path |
| Safety | Profile/photo moderation, block, report, suspension and mutual-message rules pass abuse-case testing |
| Security | Authentication/authorization review complete; secrets scan clean; dependencies free of unresolved high/critical findings |
| Privacy | Approved policies published; consent versioning and account deletion work; Play Data safety matches the implementation |
| Reliability | Backup/restore and application rollback rehearsed; monitoring and alert contacts active |
| Performance | Production-like search, discovery, image and message tests meet recorded response/load targets |
| Accessibility | Keyboard, focus, labels, contrast, text scaling and screen-reader checks cover critical flows |
| Operations | Admin and support runbooks tested; first administrators use individual accounts; audit logging verified |
| Android | Signed AAB, Play App Signing, API target compliance, closed-test acceptance and staged rollout plan complete |
| Release | Exact commit/build/schema recorded; smoke test and rollback decision points assigned |

## Monetization and post-launch work

If paid membership is required for the initial business launch, insert the following batch after Batch 6 and before compliance/store submission:

- Finalize plans and server-side entitlements.
- Integrate the approved payment provider through a provider abstraction.
- Verify signed webhooks, idempotency, reconciliation, refunds and subscription expiry.
- Add payment/admin history and member receipts without storing card data.
- Confirm Google Play billing requirements before selling digital app features inside Android.

Otherwise, release the safe free v1 first and deliver monetization as v1.1. Apple sign-in and the iOS/Xcode Cloud release follow once the shared behavior and production APIs are stable. Advanced compatibility scoring, identity-document verification, realtime presence, video calling and marketing automation are post-launch features unless separately approved.

## Recommended implementation slices

Keep pull requests reviewable and deployable in this order:

1. Production backup, rollback and SSH verification.
2. Profile/preference schema and validation.
3. Private photo storage and APIs.
4. Web photo/privacy experience.
5. Admin photo moderation.
6. Android architecture and authentication.
7. Android profile/photo experience.
8. Discovery/search/recommendation API.
9. Web discovery, favourites and interests.
10. Android discovery, favourites and interests.
11. Block/report backend and admin operations.
12. Web and Android block/report flows.
13. Messaging and notification backend.
14. Web messaging and Android messaging/push.
15. Account deletion, public policies and consent versioning.
16. Admin completion, observability and release hardening.
17. Android signing, Play testing and production release.

## Definition of done for every slice

- Server-side authorization and privacy rules are explicit.
- Relevant feature tests cover successful, forbidden and invalid cases.
- Web/admin production builds or Flutter analyze/tests/build pass as applicable.
- API changes are backward compatible within v1 or released with coordinated client updates.
- Loading, empty, error and retry states are implemented.
- No secret, private path or unnecessary personal data is logged or returned.
- Migration and rollback effects are documented.
- Staging behavior is verified after merge.
- Status, tasks, changelog and deployment records are updated when affected.
