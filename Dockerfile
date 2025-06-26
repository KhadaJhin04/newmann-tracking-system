# Use the official public image for PHP with Apache from Docker Hub
FROM php:8.2-apache

# Install necessary PHP extensions (like for PostgreSQL) and Composer
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

# Copy all your application files into the server's working directory
COPY . .

# Run composer install to download your PHP dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader

# --- THIS IS THE FIX ---
# We are explicitly setting the Apache DocumentRoot in its configuration file.
# This is more reliable than using an environment variable.
RUN echo "<Directory /var/www/html/public>\n    AllowOverride All\n</Directory>" > /etc/apache2/conf-available/document-root.conf && \
    sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf && \
    a2enconf document-root