# Lumina ERP 

Sistema de Gestão Escolar desenvolvido em **Laravel + Filament + Docker**.

## Introdução do Sistema
Lumina ERP é um sistema de gestão acadêmica, tem a finalidade de simplificar os processos...


## 🚀 Como rodar

```bash
# Crie um diretório 
mkdir /dev
git clone https://github.com/seuusuario/lumina-erp.git
cd lumina-erp
cp .env.example .env
```

```dotenv
# CONFIGURAÇÃO ARQUIVO .ENV
APP_NAME="Lumina ERP"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR

# Banco de dados
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=lumina
DB_USERNAME=dev
DB_PASSWORD=dev
```
#### Rode o container
```bash
make build
make up
# ou: docker compose build --no-cache && docker compose up -d --build
```

#### Entre no container e instale
```bash
make shell
# dentro do container:
composer install
php artisan key:generate
php artisan migrate --seed
```
Ou em uma linha: `make install` e depois `make seed`.

#### Comandos úteis (Makefile)
| Comando   | Descrição                    |
|-----------|------------------------------|
| `make up` | Sobe os containers           |
| `make down` | Para os containers         |
| `make shell` | Entra no container (zsh)  |
| `make migrate` | Roda migrations          |
| `make seed` | Migrations + seeders       |
| `make test` | PHPUnit                    |
| `make lint` | Laravel Pint (checagem)    |

---

## Infraestrutura e DevOps

- **Docker**: `Dockerfile` (PHP 8.2-FPM, Composer, Node 18), `compose.yaml` (app, nginx, MySQL 8) com healthcheck no banco e variáveis via `.env`. Dentro do container o projeto fica em **`/dev/lumina-erp`**.
- **.dockerignore**: reduz tamanho do contexto de build e acelera o build.
- **CI (GitHub Actions)**: em cada push/PR em `main` e `develop` roda **Laravel Pint**, **PHPUnit** (com MySQL em serviço) e **build da imagem Docker** (`.github/workflows/ci.yaml`).
- **Makefile**: atalhos para build, up, down, shell, migrate, seed, test e lint.

Para adicionar workers de fila no futuro, use o perfil `workers` no `compose.yaml` (serviço `queue` comentado).

<div style="
    border: 1px solid #d39aadff;
    background-color: rgba(175, 9, 180, 1);
    padding: 10px 20px;
    text-align: center;
    font-weight: bold;
    color: white;
">
    📕 Documentação Oficial
</div>

---

[📕 Documentação Oficial - Clique Aqui](./docs/index.md)

