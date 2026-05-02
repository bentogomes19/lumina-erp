FROM php:8.2-fpm

ENV LANG=pt_BR.UTF-8
ENV LANGUAGE=pt_BR:pt
ENV LC_ALL=pt_BR.UTF-8

# Instalar sudo
RUN apt-get update && apt-get install -y sudo

# Locales
RUN apt-get update && apt-get install -y locales \
    && echo "pt_BR.UTF-8 UTF-8" >> /etc/locale.gen \
    && echo "en_US.UTF-8 UTF-8" >> /etc/locale.gen \
    && locale-gen

# Dependências PHP
RUN apt-get update && apt-get install -y \
    git unzip zsh curl vim pkg-config \
    libonig-dev libzip-dev zip \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libicu-dev libxml2-dev libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring zip gd intl bcmath

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Node.js 22 (Vite 7 exige Node >=20.19 ou >=22.12)
ARG NODE_MAJOR=22
RUN curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash - \
    && apt-get install -y nodejs \
    && node -e "const [major, minor] = process.versions.node.split('.').map(Number); if (major < 22 || (major === 22 && minor < 12)) { throw new Error('Node.js >= 22.12 is required for Vite 7'); }" \
    && npm --version \
    && rm -rf /var/lib/apt/lists/*

# Criar usuário dev
RUN useradd -ms /bin/zsh dev

# Criar pasta do projeto e ajustar WORKDIR
RUN mkdir -p /dev/lumina-erp && chown -R dev:dev /dev/lumina-erp
WORKDIR /dev/lumina-erp

# Entrypoint: garante .env e APP_KEY antes de subir o php-fpm (fora do volume)
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh && chown dev:dev /entrypoint.sh

# Mudar para usuário dev
USER dev

# Instalar Oh My Zsh e tema
RUN git clone https://github.com/ohmyzsh/ohmyzsh ~/.oh-my-zsh && \
    cp ~/.oh-my-zsh/templates/zshrc.zsh-template ~/.zshrc && \
    git clone --depth=1 https://github.com/romkatv/powerlevel10k ~/.oh-my-zsh/custom/themes/powerlevel10k && \
    sed -i 's/ZSH_THEME="robbyrussell"/ZSH_THEME="agnoster"/' ~/.zshrc

# Shell padrão
SHELL ["/bin/zsh", "-c"]

# PATH correto para o composer global do dev
ENV PATH="/home/dev/.composer/vendor/bin:${PATH}"

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
