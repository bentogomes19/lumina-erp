#!/usr/bin/env bash
set -euo pipefail

if [[ $# != 1 || ! $1 =~ ^[0-9a-f]{40}$ ]]; then
    echo 'Usage: scripts/deploy-demo.sh COMMIT_SHA' >&2
    exit 1
fi

if [[ ! -f .env || ! -f compose.demo.yaml ]]; then
    echo 'Run this from the VM project directory with its existing .env.' >&2
    exit 1
fi

revision=$1
umask 077
backup_dir="$HOME/lumina-backups"
mkdir -p "$backup_dir"

compose() {
    sudo -n docker compose -f compose.demo.yaml "$@"
}

# Keep the VM's data recoverable before applying a new database migration.
backup="$backup_dir/mysql-before-${revision}-$(date -u +%Y%m%dT%H%M%SZ).sql.gz"
if ! compose exec -T db sh -c 'exec mysqldump --single-transaction -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' | gzip > "$backup"; then
    rm -f "$backup"
    echo 'Database backup failed; deployment stopped.' >&2
    exit 1
fi

compose up -d --build
compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts
compose exec -T app npm ci
compose exec -T -e NODE_OPTIONS=--max-old-space-size=1024 app npm run build
compose exec -T app php artisan package:discover --ansi
compose exec -T app php artisan filament:assets --ansi
if [[ ! -L public/storage ]]; then
    compose exec -T app php artisan storage:link
fi
compose exec -T app php artisan migrate --force
compose exec -T app php artisan config:cache
compose restart app

demo_host=$(sed -n 's/^DEMO_HOST=//p' .env | tail -n 1)
if [[ ! $demo_host =~ ^[A-Za-z0-9.-]+$ ]]; then
    echo 'DEMO_HOST is missing or invalid in the VM .env.' >&2
    exit 1
fi
curl --fail --silent --show-error --retry 5 --retry-delay 3 --max-time 30 \
    --resolve "$demo_host:443:127.0.0.1" "https://$demo_host/lumina/login" \
    --output /dev/null

printf '%s\n' "$revision" > .deployed-revision
echo "Deployment completed for $revision. Database backup: $backup"
