# Multi-stage build for Laravel Smart Meter Challenge Backend
FROM php:8.3-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    zip \
    unzip \
    git \
    mysql-client \
    sqlite \
    sqlite-dev \
    nodejs \
    npm \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    autoconf \
    g++ \
    make \
    netcat-openbsd

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_sqlite \
    gd \
    zip \
    intl \
    mbstring \
    opcache \
    bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create application directory
WORKDIR /var/www/html

# Use existing www-data user (already exists in PHP image)
# Just ensure proper permissions will be set later

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies (install all first, then prune dev after setup)
RUN composer install --no-scripts --no-autoloader

# Copy application files
COPY . .

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Generate optimized autoloader and remove dev dependencies
RUN composer dump-autoload --optimize \
    && composer install --no-dev --optimize-autoloader

# Copy configuration files
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

# Create required directories
RUN mkdir -p /var/log/nginx \
    && mkdir -p /var/log/supervisor \
    && mkdir -p /var/log/php-fpm \
    && mkdir -p /run/nginx \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && touch /var/log/nginx/access.log \
    && touch /var/log/nginx/error.log

# Set final permissions and make entrypoint executable
RUN chown -R www-data:www-data /var/www/html \
    && chown -R www-data:www-data /var/log/nginx \
    && chown -R www-data:www-data /run/nginx \
    && chmod +x /entrypoint.sh

# Expose port 80
EXPOSE 80

# Add health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/api/health || exit 1

# Use entrypoint script to initialize Laravel properly
ENTRYPOINT ["/entrypoint.sh"] 