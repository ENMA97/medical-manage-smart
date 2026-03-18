# ══════════════════════════════════════════════════════════
# ENMA Medical ERP - Combined Dockerfile (Frontend + Backend)
# Single container serving both API and SPA
# ══════════════════════════════════════════════════════════

# ── Stage 1: Build Frontend ──
FROM node:22-alpine AS frontend-build

WORKDIR /app/frontend
COPY frontend/package.json frontend/package-lock.json* ./
RUN npm ci

COPY frontend/ .
# Empty VITE_API_URL = frontend uses relative /api path (same origin)
ENV VITE_API_URL=
RUN npm run build

# ── Stage 2: PHP Base ──
FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    sqlite-dev \
    curl \
    && docker-php-ext-install pdo pdo_pgsql pgsql pdo_sqlite zip intl mbstring bcmath opcache \
    && rm -rf /tmp/pear

# OPcache settings
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.save_comments=1" >> /usr/local/etc/php/conf.d/opcache.ini

# PHP production settings
RUN echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "upload_max_filesize=20M" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "post_max_size=25M" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "max_execution_time=60" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "expose_php=Off" >> /usr/local/etc/php/conf.d/production.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ── Stage 3: Install PHP Dependencies ──
FROM base AS dependencies

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# ── Stage 4: Production ──
FROM base AS production

# Install nginx and supervisor
RUN apk add --no-cache nginx supervisor

# Copy vendor from dependencies stage
COPY --from=dependencies /var/www/html/vendor /var/www/html/vendor

# Copy backend application code
COPY backend/ .

# Copy frontend build output to public/app directory
COPY --from=frontend-build /app/frontend/dist /var/www/html/public/app

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# Create storage directories
RUN mkdir -p storage/logs storage/framework/{sessions,views,cache} bootstrap/cache database \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

# PHP-FPM pool config
RUN echo "[www]" > /usr/local/etc/php-fpm.d/zz-production.conf \
    && echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/zz-production.conf \
    && echo "pm.max_children = 20" >> /usr/local/etc/php-fpm.d/zz-production.conf \
    && echo "pm.start_servers = 3" >> /usr/local/etc/php-fpm.d/zz-production.conf \
    && echo "pm.min_spare_servers = 2" >> /usr/local/etc/php-fpm.d/zz-production.conf \
    && echo "pm.max_spare_servers = 10" >> /usr/local/etc/php-fpm.d/zz-production.conf \
    && echo "pm.max_requests = 500" >> /usr/local/etc/php-fpm.d/zz-production.conf

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

# Startup script
COPY <<'STARTSCRIPT' /start.sh
#!/bin/sh

# Dynamic PORT for Railway
PORT=${PORT:-8000}

# Create nginx config
mkdir -p /run/nginx
cat > /etc/nginx/http.d/default.conf <<NGINX
server {
    listen ${PORT};
    server_name _;

    # Frontend SPA (served from /app)
    root /var/www/html/public;
    index index.php index.html;

    client_max_body_size 20M;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript image/svg+xml;
    gzip_min_length 256;

    # API requests -> Laravel
    location /api {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Sanctum CSRF cookie
    location /sanctum {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Health check endpoint
    location = /health {
        try_files \$uri /index.php?\$query_string;
    }

    # PHP processing
    location ~ \.php\$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60;
        fastcgi_buffering off;
    }

    # Frontend static assets (with long cache)
    location /app/assets {
        alias /var/www/html/public/app/assets;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Frontend SPA - serve index.html for all non-API, non-file routes
    location / {
        try_files /app\$uri /app\$uri/ /app/index.html;
    }

    # Block dotfiles
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

# Ensure .env exists
if [ ! -f /var/www/html/.env ]; then
    cat > /var/www/html/.env <<ENV
APP_NAME="ENMA Medical ERP"
APP_ENV=${APP_ENV:-production}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}
DB_CONNECTION=${DB_CONNECTION:-sqlite}
DB_DATABASE=${DB_DATABASE:-/var/www/html/database/database.sqlite}
CORS_ALLOWED_ORIGINS=${CORS_ALLOWED_ORIGINS:-*}
ENV
fi

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force 2>&1 || true
fi

# Ensure SQLite database file exists
touch /var/www/html/database/database.sqlite 2>/dev/null || true
chown www-data:www-data /var/www/html/database/database.sqlite 2>/dev/null || true

# Laravel optimization
php artisan config:cache 2>&1 || true
php artisan route:cache 2>&1 || true
php artisan view:cache 2>&1 || true

# Database setup
php artisan migrate --force 2>&1 || true
php artisan db:seed --force 2>&1 || true

echo "=== ENMA started on port ${PORT} ==="

exec /usr/bin/supervisord -c /etc/supervisord.conf
STARTSCRIPT
RUN chmod +x /start.sh

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
    CMD curl -f http://localhost:${PORT:-8000}/health || exit 1

CMD ["/start.sh"]
