#!/usr/bin/env bash

set -euo pipefail

api_path="${1:?API path is required}"
web_path="${2:?Web path is required}"
admin_path="${3:?Admin path is required}"
release_id="${4:?Release ID is required}"

script_path="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/activate-cpanel-release.sh"
"$script_path" "$api_path" "$web_path" "$admin_path" "$release_id"

echo "Application files rolled back to $release_id. Database migrations were not reversed."
