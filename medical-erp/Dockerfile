# ══════════════════════════════════════════════════════════
# ENMA Medical ERP - Combined Dockerfile (Frontend + Backend)
# Single container: nginx + php-fpm + supervisord
# ══════════════════════════════════════════════════════════

# ── Stage 1: Build Frontend ──
FROM node:22-alpine AS frontend-build
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json* ./
RUN npm ci
COPY frontend/ .
ENV VITE_API_URL=
RUN npm run build

# ── Stage 2: Install PHP Dependencies ──
FROM composer:2 AS composer-deps
WORKDIR /app
COPY backend/composer.json ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# ── Stage 3: Production ──
FROM php:8.3-fpm-alpine AS production

# System dependencies + PHP extensions
RUN apk add --no-cache \
    nginx supervisor curl \
    postgresql-dev libzip-dev icu-dev oniguruma-dev sqlite-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql pdo_sqlite zip intl mbstring bcmath opcache \
    && rm -rf /tmp/pear /var/cache/apk/*

# PHP + OPcache production settings
RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.memory_consumption=128"; \
    echo "opcache.interned_strings_buffer=8"; \
    echo "opcache.max_accelerated_files=10000"; \
    echo "opcache.validate_timestamps=0"; \
    echo "opcache.save_comments=1"; \
    echo "memory_limit=256M"; \
    echo "upload_max_filesize=20M"; \
    echo "post_max_size=25M"; \
    echo "max_execution_time=60"; \
    echo "expose_php=Off"; \
    } > /usr/local/etc/php/conf.d/production.ini

# PHP-FPM pool config
RUN { \
    echo "[www]"; \
    echo "pm = dynamic"; \
    echo "pm.max_children = 20"; \
    echo "pm.start_servers = 3"; \
    echo "pm.min_spare_servers = 2"; \
    echo "pm.max_spare_servers = 10"; \
    echo "pm.max_requests = 500"; \
    } > /usr/local/etc/php-fpm.d/zz-production.conf

# Supervisor config (nginx + php-fpm)
RUN { \
    echo "[supervisord]"; \
    echo "nodaemon=true"; \
    echo "logfile=/dev/stdout"; \
    echo "logfile_maxbytes=0"; \
    echo "pidfile=/run/supervisord.pid"; \
    echo ""; \
    echo "[program:php-fpm]"; \
    echo "command=php-fpm -F"; \
    echo "autostart=true"; \
    echo "autorestart=true"; \
    echo "stdout_logfile=/dev/stdout"; \
    echo "stdout_logfile_maxbytes=0"; \
    echo "stderr_logfile=/dev/stderr"; \
    echo "stderr_logfile_maxbytes=0"; \
    echo ""; \
    echo "[program:nginx]"; \
    echo "command=nginx -g \"daemon off;\""; \
    echo "autostart=true"; \
    echo "autorestart=true"; \
    echo "stdout_logfile=/dev/stdout"; \
    echo "stdout_logfile_maxbytes=0"; \
    echo "stderr_logfile=/dev/stderr"; \
    echo "stderr_logfile_maxbytes=0"; \
    } > /etc/supervisord.conf

WORKDIR /var/www/html

# Copy composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy vendor from composer stage
COPY --from=composer-deps /app/vendor /var/www/html/vendor

# Copy backend application code
COPY backend/ .

# Copy frontend build output
COPY --from=frontend-build /app/dist /var/www/html/public/app

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# Create required directories
RUN mkdir -p /run/nginx storage/logs storage/framework/{sessions,views,cache} bootstrap/cache database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

# Copy startup script
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
    CMD curl -f http://localhost:${PORT:-8000}/health || exit 1

CMD ["/start.sh"]
