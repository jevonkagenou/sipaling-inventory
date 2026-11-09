FROM php:8.4-cli-alpine

# Install PHP extensions via installer helper
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql mbstring gd zip bcmath intl opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy all application files (including pre-built public/build)
COPY . .

# Install PHP production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions for Laravel writable directories
RUN chmod -R 777 storage bootstrap/cache

EXPOSE 8000

# Start Laravel production server binding to dynamic Railway $PORT
CMD sh -c "php artisan storage:link || true; php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"
