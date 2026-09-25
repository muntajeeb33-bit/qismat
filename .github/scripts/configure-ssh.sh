#!/usr/bin/env bash

set -euo pipefail

key_path="${1:?SSH key path is required}"
: "${SSH_KEY:?SSH_KEY is required}"
: "${SSH_KEY_PASSPHRASE:?SSH_KEY_PASSPHRASE is required}"
: "${HOST:?HOST is required}"

port="${PORT:-22}"
known_hosts_path="${HOME}/.ssh/known_hosts"

mkdir -p "${HOME}/.ssh"
chmod 700 "${HOME}/.ssh"
printf '%s\n' "$SSH_KEY" > "$key_path"
chmod 600 "$key_path"
timeout 20s ssh-keygen -p -q -P "$SSH_KEY_PASSPHRASE" -N '' -f "$key_path"

if [[ -n "${SSH_KNOWN_HOSTS:-}" ]]; then
    printf '%s\n' "$SSH_KNOWN_HOSTS" > "$known_hosts_path"
    chmod 600 "$known_hosts_path"
    lookup_host="$HOST"
    if [[ "$port" != "22" ]]; then
        lookup_host="[$HOST]:$port"
    fi
    ssh-keygen -F "$lookup_host" -f "$known_hosts_path" >/dev/null || {
        echo "CPANEL_SSH_KNOWN_HOSTS does not contain an entry for the configured host." >&2
        exit 1
    }
else
    echo "::warning::CPANEL_SSH_KNOWN_HOSTS is not configured; using transitional ssh-keyscan trust. Pin the verified cPanel host key before production launch."
    timeout 20s ssh-keyscan -T 10 -p "$port" "$HOST" > "$known_hosts_path"
    chmod 600 "$known_hosts_path"
fi

test -s "$known_hosts_path"
