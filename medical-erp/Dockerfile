# ══════════════════════════════════════════════════════════
# ENMA Medical ERP - Combined Dockerfile (Frontend + Backend)
# Single container: nginx + php-fpm + supervisord
# ══════════════════════════════════════════════════════════

# ── Stage 1: Build Frontend ──
FROM node:22-alpine AS frontend-build
WORKDIR /app
COPY medical-erp/frontend/package.json medical-erp/frontend/package-lock.json* ./
RUN npm ci
COPY medical-erp/frontend/ .
ENV VITE_API_URL=
RUN npm run build

# ── Stage 2: Install PHP Dependencies ──
FROM composer:2 AS composer-deps
WORKDIR /app
COPY medical-erp/backend/composer.json ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# ── Stage 3: Production ──
FROM php:8.4-fpm-alpine AS production

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
COPY medical-erp/backend/ .

# Copy frontend build output
COPY --from=frontend-build /app/dist /var/www/html/public/app

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# Create required directories
RUN mkdir -p /run/nginx storage/logs storage/framework/{sessions,views,cache} bootstrap/cache database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

# Inline startup script using shell heredoc (avoids COPY file-not-found issues)
RUN cat <<'ENDOFSCRIPT' > /start.sh
#!/bin/sh

PORT=${PORT:-8000}

mkdir -p /run/nginx
cat > /etc/nginx/http.d/default.conf <<NGINXEOF
server {
    listen ${PORT};
    server_name _;
    root /var/www/html/public;
    index index.php index.html;
    client_max_body_size 20M;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript image/svg+xml;
    gzip_min_length 256;

    location ~ ^/(api|sanctum|health) {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60;
        fastcgi_buffering off;
    }

    location ^~ /app/assets {
        alias /var/www/html/public/app/assets;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location / {
        try_files /app\$uri /app\$uri/ /app/index.html;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINXEOF

if [ ! -f /var/www/html/.env ]; then
    cat > /var/www/html/.env <<ENVEOF
APP_NAME="ENMA"
APP_ENV=${APP_ENV:-production}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-http://localhost}
APP_KEY=${APP_KEY:-}
DB_CONNECTION=${DB_CONNECTION:-sqlite}
DB_DATABASE=${DB_DATABASE:-/var/www/html/database/database.sqlite}
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
CORS_ALLOWED_ORIGINS=*
ENVEOF
fi

if [ -z "${APP_KEY}" ]; then
    php artisan key:generate --force 2>&1 || true
fi

touch /var/www/html/database/database.sqlite 2>/dev/null || true
chown www-data:www-data /var/www/html/database/database.sqlite 2>/dev/null || true
chmod 664 /var/www/html/database/database.sqlite 2>/dev/null || true

php artisan config:cache 2>&1 || true
php artisan route:cache 2>&1 || true
php artisan view:cache 2>&1 || true
php artisan migrate --force 2>&1 || true
php artisan db:seed --force 2>&1 || true

echo "=== ENMA started on port ${PORT} ==="
exec /usr/bin/supervisord -c /etc/supervisord.conf
ENDOFSCRIPT
RUN chmod +x /start.sh

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
    CMD curl -f http://localhost:${PORT:-8000}/health || exit 1

CMD ["/start.sh"]
