#!/usr/bin/env bash
set -euo pipefail

# A release is assembled from these tracked paths only. No working-tree files,
# database exports, uploads, or environment configuration enter the archive.
RELEASE_PATHS=(
    .env.example
    app artisan bootstrap config database docker lang public resources routes
    composer.json composer.lock package.json package-lock.json vite.config.js
    Dockerfile Caddyfile.demo compose.demo.yaml
    scripts/setup-demo-env.sh scripts/bootstrap-demo.sh scripts/deploy-demo.sh
)

for path in "${RELEASE_PATHS[@]}"; do
    git cat-file -e "HEAD:$path" 2>/dev/null || {
        echo "Release path is missing from this commit: $path" >&2
        exit 1
    }
done

bad=0
while IFS= read -r -d '' path; do
    lower=${path,,}
    case "/$lower" in
        */.env.example) ;;
        */.env|*/.env.*|*/id_rsa*|*/id_ed25519*|*.pem|*.key|*.p12|*.pfx|*.sql|*.sql.gz|*.tar.gz|*.zip|*.sqlite|*.sqlite3)
            echo "Sensitive file name is tracked: $path" >&2
            bad=1
            ;;
    esac
done < <(git ls-files -z)

if (( bad )); then
    exit 1
fi

# This catches common credential signatures without printing their values.
set +e
matches=$(git grep -I -l -E -e '-----BEGIN [A-Z ]*PRIVATE KEY-----|AKIA[0-9A-Z]{16}|ghp_[A-Za-z0-9]{36}' HEAD -- "${RELEASE_PATHS[@]}")
grep_status=$?
set -e
if (( grep_status > 1 )); then
    echo 'Could not inspect release source for credential signatures.' >&2
    exit 1
fi
if [[ -n $matches ]]; then
    echo 'A common private-key or access-token signature was found in release source.' >&2
    exit 1
fi

if [[ ${1:-} == --print-paths ]]; then
    printf '%s\n' "${RELEASE_PATHS[@]}"
fi
