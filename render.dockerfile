# Use the official Render image for PHP with Apache & Composer
FROM render/php:8.2-apache

# Set the document root for Apache to our 'public' directory
# This is more secure and is standard practice for modern PHP apps
ENV APACHE_DOCUMENT_ROOT public

# Copy all your project files into the server's working directory
COPY . .

# Run composer install to download your PHP dependencies (like the QR code library)
RUN composer install --no-interaction --no-dev --optimize-autoloader