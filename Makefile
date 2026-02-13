# Lumina ERP - Comandos Docker/DevOps
# Uso: make <alvo>

.PHONY: help build up down restart shell install migrate seed test lint clean

APP_CONTAINER = lumina-app

help:
	@echo "Lumina ERP - Comandos disponíveis:"
	@echo "  make build     - Build dos containers"
	@echo "  make up        - Sobe os containers em background"
	@echo "  make down      - Para e remove containers"
	@echo "  make restart   - Reinicia os containers"
	@echo "  make shell     - Entra no container da aplicação (zsh)"
	@echo "  make install   - composer install + key:generate (dentro do app)"
	@echo "  make migrate   - Roda migrations (dentro do app)"
	@echo "  make seed      - Roda migrations + seeders (dentro do app)"
	@echo "  make fresh     - migrate:fresh --seed (dentro do app)"
	@echo "  make test      - PHPUnit (dentro do app)"
	@echo "  make lint      - Laravel Pint (dentro do app)"
	@echo "  make clean     - Para containers e remove volumes"

build:
	docker compose build --no-cache

up:
	docker compose up -d --build

down:
	docker compose down

restart: down up

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
