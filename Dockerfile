FROM php:8.2-apache

# Paksa hanya prefork yang aktif
RUN a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork

# Install ekstensi MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Copy project
COPY . /var/www/html/

# Permission
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
