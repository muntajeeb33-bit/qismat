#!/usr/bin/env bash

set -euo pipefail

api_path="${1:?API path is required}"
web_path="${2:?Web path is required}"
admin_path="${3:?Admin path is required}"
release_id="${4:?Release ID is required}"

for path in "$api_path" "$web_path" "$admin_path"; do
    [[ "$path" = /* && "$path" != "/" ]] || {
        echo "Deployment paths must be absolute non-root paths." >&2
        exit 1
    }
done
[[ "$release_id" =~ ^[A-Za-z0-9._-]+$ ]] || {
    echo "Release ID contains unsupported characters." >&2
    exit 1
}

api_release="$api_path/releases/$release_id"
web_release="$web_path/releases/$release_id"
admin_release="$admin_path/releases/$release_id"

test -f "$api_release/public/index.php"
test -f "$api_release/artisan"
test -f "$web_release/index.html"
test -f "$admin_release/index.html"
test -d "$api_path/storage/app"

for entry in "$admin_path/_qismat_api.php" "$admin_path/storage"; do
    if [[ -e "$entry" && ! -L "$entry" ]]; then
        echo "Refusing to replace non-symlink deployment entry: $entry" >&2
        exit 1
    fi
done

atomic_link() {
    local target="$1"
    local link="$2"
    local temporary="${link}.next.$$"
    ln -s "$target" "$temporary"
    mv -Tf "$temporary" "$link"
}

remember_current() {
    local root="$1"
    if [[ -L "$root/current" ]]; then
        atomic_link "$(readlink "$root/current")" "$root/previous"
    fi
}

remember_current "$api_path"
remember_current "$web_path"
remember_current "$admin_path"

for root in "$web_path" "$admin_path"; do
    if [[ -e "$root/index.html" && ! -L "$root/index.html" ]]; then
        mv "$root/index.html" "$root/index.html.legacy-$release_id"
    fi
done

atomic_link "$api_release" "$api_path/current"
atomic_link "$web_release" "$web_path/current"
atomic_link "$admin_release" "$admin_path/current"

atomic_link "$web_path/current/index.html" "$web_path/index.html"
atomic_link "$admin_path/current/index.html" "$admin_path/index.html"
atomic_link "$api_path/current/public/index.php" "$admin_path/_qismat_api.php"
atomic_link "$api_path/storage/app/public" "$admin_path/storage"

printf '%s\n' "$release_id" > "$api_path/CURRENT_RELEASE"
printf '%s\n' "$release_id" > "$web_path/CURRENT_RELEASE"
printf '%s\n' "$release_id" > "$admin_path/CURRENT_RELEASE"

echo "Activated release: $release_id"
