# Use the official Render image for PHP with Apache & Composer
FROM render/php:8.2-apache

# Set the document root for Apache to our 'public' directory
ENV APACHE_DOCUMENT_ROOT public

# Copy composer files first to leverage Docker caching
# This means 'composer install' only runs when your dependencies change
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Copy the rest of your application code
COPY . .