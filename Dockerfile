FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    libpq-dev \
    libpng-dev \
    libjpeg-turbo-dev \ 
    libwebp-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    unzip \
    git

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    autoconf \
    build-base \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_pgsql bcmath gd intl zip opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY . .

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
