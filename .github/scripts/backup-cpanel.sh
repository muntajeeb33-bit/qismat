#!/usr/bin/env bash

set -euo pipefail

api_path="${1:?API path is required}"
database_name="${2:?Database name is required}"
credentials_file="${3:?Database credentials file is required}"
backup_id="${4:?Backup ID is required}"
retention_days="${5:-14}"

[[ "$api_path" = /* && "$api_path" != "/" ]] || {
    echo "API path must be an absolute non-root path." >&2
    exit 1
}
[[ "$backup_id" =~ ^[A-Za-z0-9._-]+$ ]] || {
    echo "Backup ID contains unsupported characters." >&2
    exit 1
}
[[ "$retention_days" =~ ^[0-9]+$ ]] || {
    echo "Retention days must be a non-negative integer." >&2
    exit 1
}
test -f "$credentials_file"

backup_root="$api_path/backups"
backup_path="$backup_root/$backup_id"
mkdir -p "$backup_path"
chmod 700 "$backup_root" "$backup_path"

if command -v mariadb-dump >/dev/null 2>&1; then
    dump_command=mariadb-dump
elif command -v mysqldump >/dev/null 2>&1; then
    dump_command=mysqldump
else
    echo "Neither mariadb-dump nor mysqldump is available." >&2
    exit 1
fi

"$dump_command" --defaults-extra-file="$credentials_file" --single-transaction --quick --skip-lock-tables "$database_name" \
    | gzip -9 > "$backup_path/database.sql.gz"

if [[ -d "$api_path/storage/app" ]]; then
    tar -C "$api_path" -czf "$backup_path/member-files.tar.gz" storage/app
else
    tar -czf "$backup_path/member-files.tar.gz" --files-from /dev/null
fi

{
    printf 'created_at=%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    printf 'database=%s\n' "$database_name"
    printf 'source=%s\n' "$api_path"
} > "$backup_path/manifest.txt"

(
    cd "$backup_path"
    sha256sum database.sql.gz member-files.tar.gz manifest.txt > SHA256SUMS
)

find "$backup_root" -mindepth 1 -maxdepth 1 -type d -mtime "+$retention_days" -print0 \
    | xargs -0 --no-run-if-empty rm -rf --

echo "Backup created: $backup_path"
