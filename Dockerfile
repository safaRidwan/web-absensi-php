FROM php:8.2-apache

# Install MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Copy project
COPY . /var/www/html/

# Permission
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Railway butuh listen ke PORT dinamis
CMD sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf \
    && sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-enabled/000-default.conf \
    && apache2-foreground
