FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    unzip \
    zip \
    libzip-dev \
    $PHPIZE_DEPS \
    libssl-dev \
    pkg-config \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install zip \
    && apt-get clean

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
