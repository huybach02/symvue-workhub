FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    acl \
    fcgi \
    file \
    freetype \
    gettext \
    git \
    icu-libs \
    libjpeg-turbo \
    libpng \
    libpq \
    libzip && \
    apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    freetype-dev \
    icu-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    linux-headers \
    postgresql-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-configure zip && \
    docker-php-ext-install -j"$(nproc)" gd intl opcache pdo pdo_pgsql zip && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ENV APP_ENV=prod
ENV APP_DEBUG=0

COPY composer.json composer.lock symfony.lock ./

RUN composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev && \
    APP_ENV=prod APP_DEBUG=0 composer run-script --no-dev post-install-cmd

RUN mkdir -p var/cache var/log public/uploads && \
    chmod -R 777 var public/uploads

CMD ["php-fpm"]
