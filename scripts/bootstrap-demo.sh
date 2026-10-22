#!/bin/sh
set -eu

if [ ! -f compose.demo.yaml ] || [ ! -f .env ]; then
    echo "Execute na pasta do projeto, apos criar .env." >&2
    exit 1
fi

compose() {
    sudo docker compose -f compose.demo.yaml "$@"
}

compose up -d --build
# Os seeders da demonstracao usam Faker, listado em require-dev.
compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts
compose exec -T app npm ci
compose exec -T -e NODE_OPTIONS=--max-old-space-size=1024 app npm run build
compose exec -T app php artisan package:discover --ansi
compose exec -T app php artisan filament:assets --ansi
compose exec -T app php artisan storage:link || true
compose exec -T -e APP_ENV=local app php artisan migrate --seed --force
compose exec -T app php artisan config:cache
compose ps
