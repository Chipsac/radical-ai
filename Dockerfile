# syntax=docker/dockerfile:1
#
# Radical AI — production image for Google Cloud Run.
#
# Multi-stage: front-end assets are compiled with Node, PHP dependencies are
# resolved separately, and only the runtime artefacts land in the final image.

# ---------- Stage 1: build front-end assets ----------------------------------
FROM node:22-alpine AS assets

WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
RUN npm run build


# ---------- Stage 2: resolve PHP dependencies --------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
# Scripts are skipped here because the application code isn't present yet.
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader


# ---------- Stage 3: runtime -------------------------------------------------
FROM php:8.4-fpm-alpine AS runtime

# nginx fronts php-fpm; supervisord keeps both alive in the single container
# that Cloud Run gives us.
RUN apk add --no-cache \
        nginx \
        supervisor \
        postgresql-dev \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pdo_mysql \
        bcmath \
        intl \
        zip \
        opcache \
    && apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

# Application code, then the artefacts from the earlier stages.
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /build/public/build ./public/build

# Container configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Laravel needs to write here; Cloud Run runs as a non-root user.
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Cloud Run injects PORT (8080 by default) and expects the app to listen on it.
ENV PORT=8080 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

EXPOSE 8080

ENTRYPOINT ["entrypoint"]
