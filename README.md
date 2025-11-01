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
docker compose build --no-cache
docker compose up -d --build
```

#### Entre na container
```bash
docker exec -it lumina-app zsh
```
**dentro dele, rode:**
```bash
composer install
php artisan key:generate
php artisan migrate --seed
```

