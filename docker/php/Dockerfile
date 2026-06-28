# ── Stage 1: build frontend assets ──────────────────────────────────────────
FROM node:22-alpine AS node-builder

WORKDIR /app

COPY app/package*.json ./
RUN npm ci

COPY app/ .
RUN npm run build

# ── Stage 2: install PHP dependencies ────────────────────────────────────────
FROM composer:2 AS composer-builder

WORKDIR /app

COPY app/composer.json app/composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-req=ext-pcntl

COPY app/ .
RUN composer dump-autoload --optimize

# ── Stage 3: production PHP-FPM ──────────────────────────────────────────────
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    curl-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        soap \
        mbstring \
        xml \
        zip \
        bcmath \
        intl \
        gd \
        opcache \
        pcntl \
    && apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY docker/php/conf.d/ /usr/local/etc/php/conf.d/

WORKDIR /var/www/html

COPY --from=composer-builder /app ./
COPY --from=node-builder /app/public/build ./public/build

RUN rm -f public/hot \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
