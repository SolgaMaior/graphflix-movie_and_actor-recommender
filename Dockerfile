# ---------- Frontend build stage ----------
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.js .
RUN npm run build

# ---------- PHP / Apache stage ----------
FROM php:8.3-apache

# Laravel boots during Composer package discovery. These build-time defaults
# keep the image build independent from Render's runtime environment variables.
ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libicu-dev libxml2-dev libonig-dev libcurl4-openssl-dev \
        libpq-dev \
    && docker-php-ext-install curl intl mbstring xml zip pdo_mysql pdo_pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first for better layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy app code
COPY . .
COPY --from=frontend /app/public/build ./public/build

# Apache vhost: sets DocumentRoot to /var/www/html/public directly,
# so no need for a runtime sed on DocumentRoot.
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

RUN a2dissite 000-default \
    && a2ensite 000-default \
    && printf '%s\n' 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && php artisan package:discover --ansi

# Storage symlink for public file access (safe no-op if already linked / no public disk used)
RUN php artisan storage:link || true

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

# Render injects $PORT at runtime. Apache's config uses the Listen directive
# from ports.conf, rewritten here since $PORT isn't known at build time.
CMD ["sh", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-10000}/\" /etc/apache2/ports.conf && sed -i \"s/:80>/:${PORT:-10000}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]