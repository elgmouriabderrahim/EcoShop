FROM php:8.4-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    zip \
    curl \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip


COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupadd -g ${GID} laravel \
    && useradd -u ${UID} -g laravel -m laravel

WORKDIR /var/www

RUN chown -R laravel:laravel /var/www

USER laravel