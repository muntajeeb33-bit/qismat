# M1 Profile Onboarding and Hosting Rollout

## Changes in this branch
- Adds profile moderation and explicit discovery opt-in. Existing profiles are hidden from discovery until separately reviewed and opted in.
- Allows draft profile updates, including display name; submitted profiles remain hidden until moderation approval.
- Adds status, submission and discovery API endpoints. Profile photos are not required for submission.
- Invalidates approval or pending review when reviewed public profile fields change.
- Makes Android Flutter tests mandatory.

## Important limitations
- Admin moderation approval UI/API, client registration/onboarding, secure photo access, full age-assurance policy, iOS native project and payment integration are **not** implemented here.
- The migration adds fields to an existing profiles table; it does not authorize production release.
- The approved white-background logo and QC icon must be supplied as source artwork for exact-fidelity asset exports. No replacement logo is introduced.
- Existing mobile app identifiers are unchanged.

## Hosting rollout procedure (staging first; no automatic production deploy)
1. Review and merge the feature PR after backend tests and migration checks pass. Confirm the cPanel staging host, site root, PHP version, database connection and current release SHA.
2. Take a restorable staging database backup and persistent-storage backup; test restore. Record the rollback procedure. **Before production**, separately back up production and approve its deployment.
3. Validate migrations against a copy of the current hosted schema as well as a fresh database; inspect pending SQL and confirm that no existing members become discoverable.
4. Deploy to a staging environment with separate secrets/database/media from production. If using the existing manual `.github/workflows/deploy-cpanel.yml`, confirm all `CPANEL_*` paths and database secrets point exclusively to staging before dispatch. This workflow runs `php artisan migrate --force` and rsync with `--delete`; never dispatch it against production without backup, review and explicit release authorization.
5. Run `php artisan migrate:status`, backend tests, API health check and manual authenticated flow: save a draft, submit for review, verify hidden status, simulate an authorized approval, enable/disable discovery. Check logs, storage access and CORS.
6. Monitor staging, verify rollback, and only then schedule a separately approved production release.

## No live deployment in this branch
GitHub commits do not update the live cPanel host. Production migration, staging dispatch, and release decisions remain separate actions; do not claim the hosted website or apps are updated until deployment and smoke tests succeed.
