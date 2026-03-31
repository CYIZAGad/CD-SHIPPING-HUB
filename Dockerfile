# Use official PHP image with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install necessary PHP extensions and system dependencies
RUN apt-get update && apt-get install -y \
    mariadb-client-compat \
    libmariadb-dev-compat \
    netcat-openbsd \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite for .htaccess
RUN a2enmod rewrite \
    && a2enmod headers

# Copy application files
COPY . .

# Create necessary directories with proper permissions
RUN mkdir -p /var/www/html/uploads/products \
    && mkdir -p /var/www/html/logs \
    && mkdir -p /var/www/html/sessions \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/sessions \
    && chmod -R 755 /var/www/html/uploads \
    && chmod -R 755 /var/www/html/logs \
    && chmod -R 755 /var/www/html/sessions

# Configure Apache document root
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html|g' /etc/apache2/sites-available/000-default.conf

# Set correct permissions for the app
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Copy docker entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Create sessions directory for PHP
RUN mkdir -p /var/lib/php/sessions && chown -R www-data:www-data /var/lib/php/sessions

# Configure PHP settings
RUN echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/upload.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/upload.ini \
    && echo "session.save_path = /var/lib/php/sessions" >> /usr/local/etc/php/conf.d/session.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory.ini

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Run entrypoint script and start Apache
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
