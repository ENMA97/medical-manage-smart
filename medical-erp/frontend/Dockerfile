# ── Build Stage ──
FROM node:22-alpine AS build

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

COPY . .

# Empty VITE_API_URL so frontend uses relative /api path (proxied by nginx)
ARG VITE_API_URL=
ENV VITE_API_URL=${VITE_API_URL}

RUN npm run build

# ── Production Stage ──
FROM nginx:alpine AS production

COPY --from=build /app/dist /usr/share/nginx/html

# Startup script: configure nginx with PORT and BACKEND_URL at runtime
COPY <<'STARTSCRIPT' /docker-entrypoint.d/40-configure-nginx.sh
#!/bin/sh
set -e

PORT=${PORT:-3000}
BACKEND_URL=${BACKEND_URL:-http://localhost:8000}

cat > /etc/nginx/conf.d/default.conf <<CONF
server {
    listen ${PORT};
    root /usr/share/nginx/html;
    index index.html;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript image/svg+xml;
    gzip_min_length 256;

    # SPA fallback
    location / {
        try_files \$uri \$uri/ /index.html;
    }

    # Static assets caching
    location /assets {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Reverse proxy: /api -> backend service
    location /api {
        proxy_pass ${BACKEND_URL}/api;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_read_timeout 60s;
        proxy_connect_timeout 10s;
    }

    # Health check endpoint
    location /health {
        access_log off;
        return 200 'ok';
        add_header Content-Type text/plain;
    }
}
CONF
STARTSCRIPT

RUN chmod +x /docker-entrypoint.d/40-configure-nginx.sh

EXPOSE 3000

CMD ["nginx", "-g", "daemon off;"]
