FROM php:8.1-fpm

# Set working directory
WORKDIR /var/www

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip unzip git curl \
    libonig-dev libxml2-dev libzip-dev \
    libcurl4-openssl-dev libssl-dev \
    nano default-mysql-client libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring zip exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy Laravel code
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Create Laravel storage symlink (fixes public storage access)
RUN php artisan storage:link || true

# Set correct permissions
RUN chown -R www-data:www-data /var/www

# Expose Laravel port
EXPOSE 8000

# Default command (runs the Laravel server)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
