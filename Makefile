# Lumina ERP - Comandos Docker/DevOps
# Uso: make <alvo>
#
# Todos os comandos php artisan e composer rodam DENTRO do container (lumina-app).
# Use os alvos abaixo em vez de rodar artisan no host.

.PHONY: help build up down restart rebuild shell install migrate seed test lint clean bootstrap key clear

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
	@echo "  make install   - composer install + key:generate (dentro do app)"
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

# Depois do clone: cria .env, sobe containers, instala deps e roda migrate --seed
bootstrap:
	@test -f .env || cp .env.example .env
	docker compose up -d --build
	@echo "Aguardando containers..."
	@sleep 10
	docker exec $(APP_CONTAINER) sh -c "composer install && php artisan key:generate && php artisan migrate --seed"
	@echo "Pronto. Abra http://localhost:8000 no browser."

build:
	docker compose build --no-cache

up:
	docker compose up -d --build

down:
	docker compose down

restart: down up

# Após alterar Dockerfile ou compose: rebuild da imagem e sobe os containers
rebuild:
	docker compose build --no-cache && docker compose up -d

shell:
	docker exec -it $(APP_CONTAINER) zsh

install:
	docker exec -it $(APP_CONTAINER) sh -c "composer install && php artisan key:generate"

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
