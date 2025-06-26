# Use the official public image for PHP with Apache from Docker Hub
FROM php:8.2-apache

# Install necessary PHP extensions (like for PostgreSQL) and Composer
# The FIX is adding 'libpq-dev' and 'pdo_pgsql' to this section
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory
WORKDIR /var/www/html

# Copy composer files first to leverage Docker caching
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Copy the rest of your application code
COPY . .

# Explicitly set the Apache DocumentRoot and enable .htaccess files
RUN a2enmod rewrite && \
    echo "<Directory /var/www/html/public>\n    AllowOverride All\n</Directory>" > /etc/apache2/conf-available/document-root.conf && \
    sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf && \
    a2enconf document-root