FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libjpeg62-turbo-dev libonig-dev libpng-dev libsqlite3-dev libxml2-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd intl mbstring pdo_sqlite xml zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
COPY src/composer.json src/composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts \
    && rm -rf /root/.composer/cache

COPY src/ ./

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]
