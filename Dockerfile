FROM php:8.2-apache

# Hapus semua MPM yang mungkin aktif
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load \
    && rm -f /etc/apache2/mods-enabled/mpm_*.conf

# Aktifkan hanya prefork
RUN a2enmod mpm_prefork

# Install ekstensi MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Copy project
COPY . /var/www/html/

# Permission
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
