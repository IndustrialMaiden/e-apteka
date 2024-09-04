FROM php:8.1.0-fpm-alpine

# Установка необходимых пакетов и Node.js
RUN apk add --no-cache \
    curl \
    git \
    build-base \
    zlib-dev \
    oniguruma-dev \
    autoconf \
    bash \
    mariadb-client \
    nodejs \
    npm

# Настройка не-root пользователя
ARG PUID=1000
ARG PGID=1000
RUN apk --no-cache add shadow && \
    groupmod -o -g ${PGID} www-data && \
    usermod -o -u ${PUID} -g www-data www-data

WORKDIR /var/www

# Копирование package.json и package-lock.json
COPY ./package*.json ./

# Установка npm-зависимостей
RUN npm install
RUN apk update && apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    postgresql-dev \
    && docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mysqli

# Копирование остальных исходников
COPY ./ /var/www

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

CMD php-fpm

EXPOSE 9000