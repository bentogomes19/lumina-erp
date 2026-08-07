# Lumina ERP - Comandos Docker/DevOps
# Uso: make <alvo>
#
# Todos os comandos php artisan e composer rodam DENTRO do container (lumina-app).
# Use os alvos abaixo em vez de rodar artisan no host.

.PHONY: help build up down restart rebuild shell install migrate seed fresh test lint lint-fix clean bootstrap key clear

APP_CONTAINER = lumina-app

help:
	@echo "Lumina ERP - Comandos disponíveis (artisan/composer rodam no container):"
	@echo "  make bootstrap - Clone → primeiro refresh: .env + up + install + migrate --seed"
	@echo "  make build     - Build dos containers (--no-cache)"
	@echo "  make up        - Sobe os containers em background (rebuild se necessário)"
	@echo "  make down      - Para e remove containers"
	@echo "  make restart   - Reinicia os containers"
	@echo "  make rebuild   - Rebuild completo (Dockerfile/compose) e sobe de novo"
	@echo "  make shell     - Entra no container da aplicação (zsh)"
	@echo "  make install   - composer install + npm ci/build + key:generate (dentro do app)"
	@echo "  make migrate   - Roda migrations (dentro do app)"
	@echo "  make seed      - Roda migrations + seeders (dentro do app)"
	@echo "  make fresh     - migrate:fresh --seed (dentro do app)"
	@echo "  make test      - PHPUnit (dentro do app)"
	@echo "  make lint      - Laravel Pint (dentro do app)"
	@echo "  make key       - Gera APP_KEY no .env (corrige MissingAppKeyException)"
	@echo "  make clear     - Limpa caches (config, route, view) após alterar código"
	@echo "  make clean     - Para containers e remove volumes"

# Gera APP_KEY no .env (garante .env e linha APP_KEY= antes de rodar key:generate)
key:
	docker exec $(APP_CONTAINER) sh -c "test -f .env || cp .env.example .env; grep -q '^APP_KEY=' .env 2>/dev/null || echo 'APP_KEY=' >> .env; php artisan key:generate --force"

# Limpa caches do Laravel (use após alterar .env, rotas, config, views)
clear:
	docker exec $(APP_CONTAINER) php artisan optimize:clear

# Depois do clone:
# 1. cria .env
# 2. sobe os containers
# 3. instala Composer sem executar scripts do Laravel
# 4. compila os assets Vite
# 5. inicializa Laravel/Filament
# 6. executa migrations e seeders
bootstrap:
	@test -f .env || cp .env.example .env
	@echo "Subindo containers..."
	docker compose up -d --build
	@echo "Instalando dependências PHP..."
	docker exec $(APP_CONTAINER) composer install \
		--no-interaction \
		--prefer-dist \
		--optimize-autoloader \
		--no-scripts
	@echo "Instalando dependências JavaScript..."
	docker exec $(APP_CONTAINER) npm ci
	@echo "Compilando assets com Vite..."
	docker exec $(APP_CONTAINER) npm run build
	@echo "Gerando a chave da aplicação..."
	docker exec $(APP_CONTAINER) sh -c \
		"grep -q '^APP_KEY=' .env 2>/dev/null || echo 'APP_KEY=' >> .env; \
		php artisan key:generate --force"
	@echo "Descobrindo pacotes Laravel..."
	docker exec $(APP_CONTAINER) php artisan package:discover --ansi
	@echo "▶ Publicando assets do Filament..."
	docker exec $(APP_CONTAINER) php artisan filament:assets --ansi
	@echo "▶ Executando migrations e seeders..."
	docker exec $(APP_CONTAINER) php artisan migrate --seed --force
	@echo "▶ Limpando caches..."
	docker exec $(APP_CONTAINER) php artisan optimize:clear
	@echo "✅ Bootstrap concluído."

build:
	docker compose build --no-cache

up:
	@test -f .env || cp .env.example .env
	docker compose up -d --build

down:
	docker compose down

restart: down up

# Após alterar Dockerfile ou compose: rebuild da imagem e sobe os containers
rebuild:
	@test -f .env || cp .env.example .env
	docker compose build --no-cache && docker compose up -d

shell:
	docker exec -it $(APP_CONTAINER) zsh

install:
	@echo "▶ Instalando dependências PHP..."
	docker exec $(APP_CONTAINER) composer install \
		--no-interaction \
		--prefer-dist \
		--optimize-autoloader \
		--no-scripts
	@echo "▶ Compilando..."
	docker exec $(APP_CONTAINER) npm ci
	docker exec $(APP_CONTAINER) npm run build
	@echo "▶ Finalizando Laravel e Filament..."
	docker exec $(APP_CONTAINER) php artisan package:discover --ansi
	docker exec $(APP_CONTAINER) php artisan filament:assets --ansi
	docker exec $(APP_CONTAINER) php artisan key:generate --force
	docker exec $(APP_CONTAINER) php artisan optimize:clear
	@echo "✅ Dependências instaladas."

migrate:
	docker exec -it $(APP_CONTAINER) php artisan migrate

seed:
	docker exec -it $(APP_CONTAINER) php artisan migrate --seed

fresh:
	docker exec -it $(APP_CONTAINER) php artisan migrate:fresh --seed

test:
	docker exec -it $(APP_CONTAINER) ./vendor/bin/phpunit

lint:
	docker exec -it $(APP_CONTAINER) ./vendor/bin/pint --test

lint-fix:
	docker exec -it $(APP_CONTAINER) ./vendor/bin/pint

clean:
	docker compose down -v
