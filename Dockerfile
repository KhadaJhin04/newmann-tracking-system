# Use the official public image for PHP with Apache from Docker Hub
FROM php:8.2-apache

# Set the document root for Apache to our 'public' directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Install necessary PHP extensions (like for PostgreSQL) and Composer
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy composer files first to leverage Docker caching
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Copy the rest of your application code
COPY . .

# Set correct permissions for storage and cache folders if they exist
# RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache