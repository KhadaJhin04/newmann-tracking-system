# Use the official public image for PHP with Apache as a base
FROM php:8.2-apache

# Install all necessary system dependencies first, including those for PHP extensions and Composer
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions one by one for clarity and better error handling
RUN docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install pdo pdo_pgsql zip

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory for the application
WORKDIR /var/www/html

# Copy composer files first to leverage Docker caching
COPY composer.json composer.lock ./
# Run composer install to download your PHP dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Copy the rest of your application code
COPY . .

# Set the correct ownership for web server access
RUN chown -R www-data:www-data /var/www/html

# Enable Apache's rewrite module and set the correct document root
RUN a2enmod rewrite && \
    echo "<Directory /var/www/html/public>\n    AllowOverride All\n</Directory>" > /etc/apache2/conf-available/document-root.conf && \
    sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf && \
    a2enconf document-root