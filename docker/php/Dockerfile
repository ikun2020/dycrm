FROM php:8.3-fpm-alpine

RUN set -eux; \
    apk add --no-cache \
        bash \
        curl \
        freetype \
        git \
        icu-libs \
        libjpeg-turbo \
        libpng \
        libxml2 \
        libzip \
        oniguruma \
        unzip \
        zip; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        curl-dev \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        oniguruma-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install \
        bcmath \
        curl \
        dom \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        simplexml \
        xml \
        xmlreader \
        xmlwriter \
        zip; \
    apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer validate --no-check-publish \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts \
    && php artisan package:discover --ansi \
    && mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

CMD ["php-fpm"]
