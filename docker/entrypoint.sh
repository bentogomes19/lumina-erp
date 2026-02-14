#!/bin/sh
set -e
cd /dev/lumina-erp

# Garante que .env existe (volume pode estar vazio na primeira subida)
if [ ! -f .env ]; then
	cp .env.example .env
fi

# Garante que existe a linha APP_KEY= (exigida pelo key:generate)
if ! grep -q '^APP_KEY=' .env 2>/dev/null; then
	echo 'APP_KEY=' >> .env
fi

# Gera APP_KEY se o artisan estiver disponível (após composer install)
if [ -f vendor/autoload.php ]; then
	php artisan key:generate --force --no-interaction 2>/dev/null || true
fi

exec "$@"
