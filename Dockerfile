FROM php:8.3-fpm-alpine

# Diretório de trabalho
WORKDIR /var/www

# Instalar dependências do sistema
RUN apk add --no-cache \
    zip \
    unzip \
    bash \
    libzip-dev \
    oniguruma-dev

# Instalar extensões PHP
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    bcmath \
    zip

# Copiar composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar o codigo e instalar dependencias PHP
COPY . .
RUN composer install --no-interaction --prefer-dist --no-progress

# Criar usuário
RUN adduser -D -g '' laravel

# Permissões
RUN chown -R laravel:laravel /var/www

USER laravel