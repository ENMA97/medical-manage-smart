# ── Development Stage ──
FROM node:22-alpine AS development

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

COPY . .

EXPOSE 3000

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "3000"]

# ── Build Stage ──
FROM node:22-alpine AS build

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

COPY . .

ARG VITE_API_URL=http://localhost:8000/api
ENV VITE_API_URL=${VITE_API_URL}

RUN npm run build

# ── Production Stage ──
FROM nginx:alpine AS production

COPY --from=build /app/dist /usr/share/nginx/html

# SPA fallback - use PORT env var (Railway sets it), default to 3000
COPY <<'EOF' /docker-entrypoint.d/40-configure-port.sh
#!/bin/sh
PORT=${PORT:-3000}
cat > /etc/nginx/conf.d/default.conf <<CONF
server {
    listen ${PORT};
    root /usr/share/nginx/html;
    index index.html;
    location / {
        try_files \$uri \$uri/ /index.html;
    }
    location /assets {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
CONF
EOF

RUN chmod +x /docker-entrypoint.d/40-configure-port.sh

EXPOSE 3000

CMD ["nginx", "-g", "daemon off;"]
