FROM php:8.2-fpm

ENV LANG=pt_BR.UTF-8
ENV LANGUAGE=pt_BR:pt
ENV LC_ALL=pt_BR.UTF-8

RUN apt-get update && apt-get install -y locales \
    && echo "pt_BR.UTF-8 UTF-8" >> /etc/locale.gen \
    && echo "en_US.UTF-8 UTF-8" >> /etc/locale.gen \
    && locale-gen

RUN apt-get update && apt-get install -y \
    git unzip zsh curl vim pkg-config \
    libonig-dev libzip-dev zip \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libicu-dev libxml2-dev libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring zip gd intl bcmath

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Criar usuário dev
RUN useradd -ms /bin/zsh dev
USER dev
WORKDIR /home/dev/lumina-erp

# Instalar Oh My Zsh manualmente + P10K
RUN git clone https://github.com/ohmyzsh/ohmyzsh.git ~/.oh-my-zsh && \
    cp ~/.oh-my-zsh/templates/zshrc.zsh-template ~/.zshrc && \
    git clone --depth=1 https://github.com/romkatv/powerlevel10k.git ~/.oh-my-zsh/custom/themes/powerlevel10k && \
    sed -i 's/ZSH_THEME="robbyrussell"/ZSH_THEME="agnoster"/' ~/.zshrc

SHELL ["/bin/zsh", "-c"]

ENV PATH="/home/dev/.composer/vendor/bin:${PATH}"

CMD ["php-fpm"]
