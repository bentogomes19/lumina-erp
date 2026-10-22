#!/bin/sh
set -eu

if [ "$#" -ne 1 ]; then
    echo "Uso: $0 nome-publico (exemplo: 35-215-249-75.sslip.io)" >&2
    exit 1
fi

if [ -e .env ]; then
    echo "O arquivo .env ja existe. Nada foi alterado." >&2
    exit 1
fi

if [ ! -f .env.example ]; then
    echo "Execute este script na pasta do projeto." >&2
    exit 1
fi

DEMO_HOST=$1
case "$DEMO_HOST" in
    *[!a-zA-Z0-9.-]*|'')
        echo "Nome publico invalido." >&2
        exit 1
        ;;
esac

umask 077
cp .env.example .env
export DEMO_HOST

python3 - <<'PY'
import base64
import os
import secrets
from pathlib import Path

path = Path('.env')
changes = {
    'APP_ENV': 'production',
    'APP_KEY': 'base64:' + base64.b64encode(secrets.token_bytes(32)).decode(),
    'APP_DEBUG': 'false',
    'APP_URL': 'https://' + os.environ['DEMO_HOST'],
    'LOG_LEVEL': 'warning',
    'DB_HOST': 'db',
    'DB_DATABASE': 'lumina',
    'DB_USERNAME': 'lumina',
    'DB_PASSWORD': secrets.token_hex(24),
    'DB_ROOT_PASSWORD': secrets.token_hex(24),
    'DEMO_HOST': os.environ['DEMO_HOST'],
    'APP_UID': str(os.getuid()),
    'APP_GID': str(os.getgid()),
    'SCHOOL_DEFAULT_PASSWORD': secrets.token_hex(12),
    'QUEUE_CONNECTION': 'sync',
    'SESSION_ENCRYPT': 'true',
    'SESSION_SECURE_COOKIE': 'true',
}

lines = []
seen = set()
for line in path.read_text().splitlines():
    key = line.split('=', 1)[0]
    if key in changes:
        lines.append(f'{key}={changes[key]}')
        seen.add(key)
    else:
        lines.append(line)
for key, value in changes.items():
    if key not in seen:
        lines.append(f'{key}={value}')
path.write_text('\n'.join(lines) + '\n')
PY

chmod 600 .env
echo "Configuracao criada em .env. Guarde o valor de SCHOOL_DEFAULT_PASSWORD; nao envie capturas dele."
