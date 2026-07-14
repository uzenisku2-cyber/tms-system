FROM php:8.4-fpm


RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        mbstring \
        intl \
        zip \
        bcmath \
        exif \
        pcntl


COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


WORKDIR /var/www