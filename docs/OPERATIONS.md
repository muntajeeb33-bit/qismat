# Qismat Operations Runbook

Last updated: 2026-09-25

## Release layout

The cPanel deployment keeps shared state outside immutable application releases:

```text
CPANEL_API_PATH/
├── current -> releases/<release-id>
├── previous -> releases/<previous-release-id>
├── releases/<release-id>/
├── storage/
├── .env
├── .secrets/
├── backups/<backup-id>/
└── deployment/
```

The member and admin document roots use the same `current` and `previous` release links for their generated `index.html`. Hashed frontend assets are retained at the document root so an already-open page can still load its preceding release assets during a deployment or rollback.

Release IDs use `<github-run-id>-<12-character-commit>`. Each backend release also contains `RELEASE_SHA`, while each application root records `CURRENT_RELEASE`.

## Normal deployment

`deploy-cpanel.yml` performs these operations in order:

1. Build Laravel dependencies and both React applications in GitHub Actions.
2. Configure SSH and prefer the pinned `CPANEL_SSH_KNOWN_HOSTS` value.
3. Verify database access.
4. Create a compressed database and member-file snapshot.
5. Upload a new immutable backend, member-web and admin release.
6. Link the backend release to shared environment, credentials and storage.
7. Run forward database migrations against the staged release.
8. Switch the three `current` links and stable entry points.
9. Verify the API, member website and admin website.
10. Return to the preceding release automatically when verification fails.

Application rollback never reverses database migrations. Every migration must remain compatible with the preceding application release until the new release is stable.

## SSH host verification

Add the repository secret `CPANEL_SSH_KNOWN_HOSTS` before production launch. It must contain the exact OpenSSH `known_hosts` line supplied or independently verified through the hosting provider. For a nonstandard port, the host field normally uses `[hostname]:port`.

The workflows currently permit transitional `ssh-keyscan` trust when the secret is absent and emit a warning. Remove that fallback after the verified secret has passed cPanel preflight and deployment.

Never build the pinned value from an unverified connection during an incident. A changed host key must be confirmed with the hosting provider before updating the secret.

## Backups

The deployment creates `predeploy-<release-id>` before migrations. `backup-cpanel.yml` also runs daily and can be dispatched manually. Each backup contains:

- `database.sql.gz`
- `member-files.tar.gz`
- `manifest.txt`
- `SHA256SUMS`

Backup directories are private to the cPanel account and retained for 14 days. This protects deployments and routine data loss, but it is not sufficient for hosting-account or server loss. Configure an encrypted off-host copy before production launch and document its retention and restore owner.

## Verify a backup

From an authenticated server shell:

```bash
cd "$CPANEL_API_PATH/backups/<backup-id>"
sha256sum -c SHA256SUMS
gzip -t database.sql.gz
tar -tzf member-files.tar.gz >/dev/null
```

Do not print database rows, credentials or member-file contents into CI logs.

## Application rollback

Use the **Roll Back cPanel Release** workflow and supply an existing release ID. The workflow checks that matching backend, member-web and admin release directories exist, switches all entry points, and verifies the public endpoints.

After rollback:

1. Confirm the API health response.
2. Test member and administrator authentication.
3. Test the feature affected by the incident.
4. Record the release ID, reason, verification and database migration state in `DEPLOYMENT_LOG.md`.

## Data restore rehearsal

Restore into a separate non-production database and media directory first. Verify the checksums, import the compressed SQL, extract member files, run the application against the restored environment and test authentication, profile retrieval and representative media access.

A production restore requires a maintenance window, a fresh safety snapshot and an explicit review of data created after the selected backup. Do not automatically reverse migrations or overwrite live member files. Record the chosen backup, incident window, validation and owner in `DEPLOYMENT_LOG.md`.

## Release acceptance

A deployment is complete only when:

- API, member-web and admin health checks pass.
- `CURRENT_RELEASE` matches the intended GitHub run and commit.
- Required migrations completed.
- The pre-deployment backup checksum manifest is valid.
- No secret or personal member data appeared in logs.
- Any rollback or unusual intervention is recorded.
