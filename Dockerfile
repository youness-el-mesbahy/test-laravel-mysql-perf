# ==========================================
# Stage 1: Build dependencies with Composer (PHP 8.4 compatible)
# ==========================================
FROM php:8.4-cli-alpine AS builder

WORKDIR /app

# Copy composer from the official image
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

# Install vendor dependencies without dev requirements and scripts
RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader

# ==========================================
# Stage 2: Runtime Production Image
# ==========================================
FROM php:8.4-fpm-alpine

# Set working directory
WORKDIR /var/www

# Install system dependencies & PHP extensions
RUN apk add --no-cache \
        libzip-dev \
        libpng-dev \
        libxml2-dev \
        oniguruma-dev \
        zip \
        unzip \
    && docker-php-ext-install \
        pdo_mysql \
        bcmath \
        zip \
        opcache

# Copy custom PHP & Opcache configurations
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY ./docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy build dependencies from Stage 1
COPY --from=builder /app/vendor /var/www/vendor

# Copy application files
COPY . /var/www

# Set appropriate permissions for storage and cache directories
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Set entrypoint
ENTRYPOINT ["/var/www/docker/entrypoint.sh"]
