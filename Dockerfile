FROM php:8.4-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql zip mbstring bcmath \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Use Composer from the official image (avoids installing it manually)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy everything — composer.json, composer.lock, all app files
COPY . .

# Install PHP dependencies using the pinned composer.lock (no internet guessing)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create all required storage and cache directories, then fix ownership.
# www-data (Apache) must be able to read all app files and write to storage/cache.
RUN mkdir -p \
    storage/app/public/lessons \
    storage/logs \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Point Apache at Laravel's public/ directory
RUN sed -i 's|/var/www/html|/var/www/html/public|g' \
    /etc/apache2/sites-available/000-default.conf

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
