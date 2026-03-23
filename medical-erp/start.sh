#!/bin/sh

PORT=${PORT:-8000}

# ── Nginx config ──
mkdir -p /run/nginx
cat > /etc/nginx/http.d/default.conf <<NGINXCONF
server {
    listen ${PORT};
    server_name _;
    root /var/www/html/public;
    index index.php index.html;
    client_max_body_size 20M;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript image/svg+xml;
    gzip_min_length 256;

    # API + sanctum + health -> Laravel
    location ~ ^/(api|sanctum|health) {
        try_files \$uri \$uri/ /index.php?\$query_string;
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
    location ^~ /assets/ {
        root /var/www/html/public/app;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Frontend SPA - everything else
    location / {
        root /var/www/html/public/app;
        index index.html;
        try_files \$uri \$uri/ /index.html;
    }

    # Block dotfiles
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINXCONF

# ── Laravel .env ──
if [ ! -f /var/www/html/.env ]; then
    cat > /var/www/html/.env <<ENVFILE
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
ENVFILE
fi

# ── APP_KEY ──
if [ -z "${APP_KEY}" ]; then
    php artisan key:generate --force 2>&1 || true
fi

# ── SQLite file ──
touch /var/www/html/database/database.sqlite 2>/dev/null || true
chown www-data:www-data /var/www/html/database/database.sqlite 2>/dev/null || true
chmod 664 /var/www/html/database/database.sqlite 2>/dev/null || true

# ── Laravel caches ──
php artisan config:cache 2>&1 || true
php artisan route:cache 2>&1 || true
php artisan view:cache 2>&1 || true

# ── Database ──
php artisan migrate --force 2>&1 || true
php artisan db:seed --force 2>&1 || true

echo "=== ENMA started on port ${PORT} ==="
exec /usr/bin/supervisord -c /etc/supervisord.conf
