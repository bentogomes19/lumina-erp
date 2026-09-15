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

# Gera APP_KEY somente quando ela ainda estiver vazia.
# Regenerá-la a cada reinício invalida todas as sessões existentes.
if [ -f vendor/autoload.php ] && ! grep -q '^APP_KEY=.\+' .env 2>/dev/null; then
	php artisan key:generate --no-interaction 2>/dev/null || true
fi

exec "$@"
