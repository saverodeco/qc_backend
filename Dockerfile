FROM php:8.4-fpm

# --- System dependencies ---
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    gettext-base \
    git \
    unzip \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# --- PHP extensions Laravel needs ---
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        bcmath \
        opcache

# --- Composer ---
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# --- App code ---
COPY . .

# Install PHP deps (no dev, optimized autoloader)
# Composer kadang OOM waktu extract package besar (Symfony, dll) di builder
# dengan memori terbatas — matikan limit memori composer sendiri.
ENV COMPOSER_MEMORY_LIMIT=-1

RUN composer install --no-dev --optimize-autoloader --no-interaction

# Laravel needs write access here; if you attach a Render Persistent Disk,
# mount it at /var/www/html/storage/app/public so uploaded QC photos
# survive redeploys.
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# --- Nginx + Supervisor config ---
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]