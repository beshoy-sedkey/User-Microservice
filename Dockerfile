# Use an official PHP runtime as a parent image
FROM php:8.2

# Install necessary system packages
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    zlib1g-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    graphviz \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mysqli zip sockets \
    && docker-php-source delete

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /app

# Copy composer.json and composer.lock files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --prefer-dist --no-scripts --no-autoloader && rm -rf /root/.composer

# Copy application source
COPY . .

RUN composer dump-autoload --optimize

# Start the PHP server
CMD php artisan serve --host=0.0.0.0

# Expose port 8000
EXPOSE 8001
