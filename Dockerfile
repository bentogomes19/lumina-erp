# Etapa 1 - Dependências PHP + Node + Composer
FROM php:8.3-fpm

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev nodejs npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instala Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Cria usuário sem root (melhor prática)
RUN useradd -m laravel

# Define diretório de trabalho
WORKDIR /var/www/html

# Copia arquivos do projeto
COPY . .

# Ajusta permissões
RUN chown -R laravel:laravel /var/www/html/storage /var/www/html/bootstrap/cache

# Instala dependências PHP e Node
RUN composer install --no-interaction --prefer-dist && npm install && npm run build

USER laravel

CMD ["php-fpm"]
