FROM php:8.4-fpm-bookworm

# Install Node.js 22 (required by Vite 7+)
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

# Copy composer files and run composer update
COPY composer.json ./
RUN composer update --no-scripts --no-interaction --optimize-autoloader --prefer-dist

# Copy rest of application
COPY . .

# Install npm and build assets
RUN npm install && npm run build

# Set permissions
RUN mkdir -p storage/framework/{sessions,views,cache,testing} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080

CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && php-fpm"]
