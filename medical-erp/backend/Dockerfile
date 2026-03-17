FROM php:8.4-cli-bookworm

# Install Node.js 22
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash -

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip nodejs libpng-dev libjpeg-dev libfreetype6-dev \
    libpq-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql gd zip opcache bcmath pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies
COPY composer.json ./
RUN composer update --no-scripts --no-interaction --optimize-autoloader --prefer-dist

# Copy application
COPY . .

# Build frontend assets
RUN npm install && npm run build

# Set permissions
RUN mkdir -p storage/framework/{sessions,views,cache,testing} storage/logs bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 8080

CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force --no-interaction && php artisan serve --host=0.0.0.0 --port=8080"]
