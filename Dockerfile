FROM php:8.4-fpm

# System deps
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libsqlite3-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    pkg-config \
    && docker-php-ext-install \
        pdo \
        pdo_sqlite \
        zip \
        intl \
        mbstring \
        bcmath \
        opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Allow composer as root
ENV COMPOSER_ALLOW_SUPERUSER=1

# Set working dir
WORKDIR /var/www

# Copy app
COPY . .

# Install PHP deps
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

# Permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

EXPOSE 9000
CMD ["php-fpm"]
