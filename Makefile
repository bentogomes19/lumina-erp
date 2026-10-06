# Lumina ERP - Comandos Docker/DevOps
# Uso: make <alvo>
#
# Todos os comandos php artisan e composer rodam DENTRO do container (lumina-app).
# Use os alvos abaixo em vez de rodar artisan no host.

.PHONY: help build up down restart rebuild shell install migrate seed fresh test lint lint-fix clean bootstrap key clear ssh

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
	@echo "  make ssh       - Configura e testa a chave SSH do GitHub (no host)"
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
	docker exec $(APP_CONTAINER) sh -c "test -f .env || cp .env.example .env; grep -q '^APP_KEY=' .env 2>/dev/null || echo 'APP_KEY=' >> .env; grep -q '^APP_KEY=.\+' .env 2>/dev/null || php artisan key:generate --no-interaction"

# Configura SSH no host: o compose monta ~/.ssh no container como somente leitura.
ssh:
	@set -eu; \
	ssh_dir="$$HOME/.ssh"; \
	key="$$ssh_dir/id_ed25519"; \
	printf "Informe seu nome para identificar os commits: "; \
	read -r name; \
	if [ -z "$$name" ]; then echo "O nome não pode ficar vazio." >&2; exit 1; fi; \
	printf "Informe seu e-mail para identificar a chave SSH: "; \
	read -r email; \
	if [ -z "$$email" ]; then echo "O e-mail não pode ficar vazio." >&2; exit 1; fi; \
	if [ ! -f "$$key" ]; then \
		mkdir -p "$$ssh_dir"; chmod 700 "$$ssh_dir"; \
		ssh-keygen -t ed25519 -C "$$email" -f "$$key" -N ""; \
	else \
		echo "Chave existente encontrada: $$key (o e-mail informado não altera a chave existente)"; \
	fi; \
	if [ ! -f "$$key.pub" ]; then echo "Arquivo de chave pública não encontrado: $$key.pub" >&2; exit 1; fi; \
	echo; echo "Copie a chave pública abaixo e adicione em https://github.com/settings/keys"; \
	echo "(Settings → SSH and GPG keys → New SSH key)"; echo; cat "$$key.pub"; echo; \
	printf "Depois de salvar a chave no GitHub, pressione ENTER para testar (Ctrl+C para cancelar): "; \
	read -r confirm; \
	set +e; ssh_output=$$(ssh -o StrictHostKeyChecking=accept-new -T git@github.com 2>&1); ssh_status=$$?; set -e; \
	printf '%s\n' "$$ssh_output"; \
	if printf '%s\n' "$$ssh_output" | grep -q "successfully authenticated"; then \
		git config --local user.name "$$name"; \
		git config --local user.email "$$email"; \
		origin_url=$$(git remote get-url origin); \
		case "$$origin_url" in \
			https://github.com/*) repo_path=$${origin_url#https://github.com/}; ssh_url="git@github.com:$$repo_path" ;; \
			git@github.com:*) ssh_url="$$origin_url" ;; \
			*) echo "Origin não é um URL GitHub compatível: $$origin_url" >&2; exit 1 ;; \
		esac; \
		git remote set-url origin "$$ssh_url"; \
		echo "✅ Conexão SSH com o GitHub validada."; \
		echo "✅ Identidade de commit configurada neste repositório."; \
		echo "✅ Origin configurado para SSH: $$(git remote get-url origin)"; \
	else \
		echo "❌ Não foi possível autenticar no GitHub. Confira se a chave foi adicionada à conta correta e tente novamente." >&2; exit 1; \
	fi

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
		grep -q '^APP_KEY=.\+' .env 2>/dev/null || php artisan key:generate --no-interaction"
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
	docker exec $(APP_CONTAINER) sh -c "grep -q '^APP_KEY=.\+' .env 2>/dev/null || php artisan key:generate --no-interaction"
	docker exec $(APP_CONTAINER) php artisan optimize:clear
	@echo "✅ Dependências instaladas."

migrate:
	docker exec -it $(APP_CONTAINER) php artisan migrate

seed:
	docker exec -it $(APP_CONTAINER) php artisan migrate --seed

fresh:
	docker exec -it $(APP_CONTAINER) php artisan migrate:fresh --seed

test:
	docker exec $(APP_CONTAINER) php artisan config:clear --no-ansi
	docker exec -it $(APP_CONTAINER) ./vendor/bin/phpunit

lint:
	docker exec -it $(APP_CONTAINER) ./vendor/bin/pint --test

lint-fix:
	docker exec -it $(APP_CONTAINER) ./vendor/bin/pint

clean:
	docker compose down -v
