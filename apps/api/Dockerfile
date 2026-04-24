FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    linux-headers \
    autoconf \
    dpkg-dev \
    dpkg \
    file \
    g++ \
    gcc \
    libc-dev \
    make \
    pkgconf \
    re2c \
    nodejs \
    npm \
    nginx

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy entire app first (so artisan exists for post-autoload-dump)
COPY . .

# Install PHP deps (dev dependencies included for artisan commands)
RUN composer install --no-interaction --optimize-autoloader --ignore-platform-reqs

# Copy Nginx configuration
COPY nginx.conf /etc/nginx/nginx.conf

# Copy startup script and make it executable
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Ensure storage and cache directories are writable
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Expose port
EXPOSE 8000

# Start Nginx + PHP-FPM
CMD ["/start.sh"]
