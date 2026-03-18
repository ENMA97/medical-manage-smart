# ══════════════════════════════════════════════
# Medical ERP Backend - Production Dockerfile
# ══════════════════════════════════════════════

# ── Base Stage ──
FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    sqlite-dev \
    curl \
    && docker-php-ext-install pdo pdo_pgsql pgsql pdo_sqlite zip intl mbstring bcmath opcache \
    && pecl install redis && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

# OPcache production settings
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.save_comments=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# PHP production settings
RUN echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "upload_max_filesize=20M" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "post_max_size=25M" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "max_execution_time=60" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "expose_php=Off" >> /usr/local/etc/php/conf.d/production.ini

# PHP-FPM pool config
RUN echo "[www]" > /usr/local/etc/php-fpm.d/zz-production.conf \
    && echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/zz-production.conf \
    && echo "pm.max_children = 50" >> /usr/local/etc/php-fpm.d/zz-production.conf \
    && echo "pm.start_servers = 5" >> /usr/local/etc/php-fpm.d/zz-production.conf \
    && echo "pm.min_spare_servers = 5" >> /usr/local/etc/php-fpm.d/zz-production.conf \
    && echo "pm.max_spare_servers = 35" >> /usr/local/etc/php-fpm.d/zz-production.conf \
    && echo "pm.max_requests = 500" >> /usr/local/etc/php-fpm.d/zz-production.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── Dependencies Stage ──
FROM base AS dependencies

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# ── Production Stage ──
FROM base AS production

# Install nginx and supervisor
RUN apk add --no-cache nginx supervisor

# Nginx config template (PORT replaced at startup)
RUN mkdir -p /run/nginx && echo 'server { \
    listen __PORT__; \
    server_name _; \
    root /var/www/html/public; \
    index index.php; \
    \
    client_max_body_size 20M; \
    \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; \
        include fastcgi_params; \
        fastcgi_read_timeout 60; \
        fastcgi_buffering off; \
    } \
    \
    location ~ /\.(?!well-known).* { \
        deny all; \
    } \
}' > /etc/nginx/http.d/default.conf.template

# Supervisor config
RUN echo '[supervisord]' > /etc/supervisord.conf \
    && echo 'nodaemon=true' >> /etc/supervisord.conf \
    && echo 'logfile=/var/log/supervisord.log' >> /etc/supervisord.conf \
    && echo 'pidfile=/run/supervisord.pid' >> /etc/supervisord.conf \
    && echo '' >> /etc/supervisord.conf \
    && echo '[program:php-fpm]' >> /etc/supervisord.conf \
    && echo 'command=php-fpm -F' >> /etc/supervisord.conf \
    && echo 'autostart=true' >> /etc/supervisord.conf \
    && echo 'autorestart=true' >> /etc/supervisord.conf \
    && echo '' >> /etc/supervisord.conf \
    && echo '[program:nginx]' >> /etc/supervisord.conf \
    && echo 'command=nginx -g "daemon off;"' >> /etc/supervisord.conf \
    && echo 'autostart=true' >> /etc/supervisord.conf \
    && echo 'autorestart=true' >> /etc/supervisord.conf

# Copy vendor from dependencies stage
COPY --from=dependencies /var/www/html/vendor /var/www/html/vendor

# Copy application code
COPY . .

# Generate autoloader and optimize
RUN composer dump-autoload --optimize

# Create storage directories with proper permissions
RUN mkdir -p storage/logs storage/framework/{sessions,views,cache} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Startup script
COPY <<'STARTSCRIPT' /start.sh
#!/bin/sh
set -e

# Dynamic PORT for Railway
PORT=${PORT:-8000}
sed "s/__PORT__/$PORT/g" /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf

# Ensure .env exists (Railway passes env vars, but Laravel needs the file for artisan)
if [ ! -f /var/www/html/.env ]; then
    echo "APP_NAME=\"Medical ERP\"" > /var/www/html/.env
    echo "APP_ENV=${APP_ENV:-production}" >> /var/www/html/.env
    echo "APP_DEBUG=${APP_DEBUG:-false}" >> /var/www/html/.env
    echo "APP_URL=${APP_URL:-http://localhost}" >> /var/www/html/.env
    echo "DB_CONNECTION=${DB_CONNECTION:-sqlite}" >> /var/www/html/.env
    echo "CORS_ALLOWED_ORIGINS=${CORS_ALLOWED_ORIGINS:-*}" >> /var/www/html/.env
fi

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force 2>&1
fi

# Ensure SQLite database file exists
touch /var/www/html/database/database.sqlite 2>/dev/null || true

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force 2>&1 || true
php artisan db:seed --force 2>&1 || true

exec /usr/bin/supervisord -c /etc/supervisord.conf
STARTSCRIPT
RUN chmod +x /start.sh

EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
    CMD curl -f http://localhost:${PORT:-8000}/health || exit 1

CMD ["/start.sh"]
