FROM php:8.2-apache

# Matikan modul yang bikin bentrok dan aktifkan yang benar
RUN a2dismod mpm_event && a2enmod mpm_prefork

# Instal ekstensi MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Salin semua file proyek
COPY . /var/www/html/

# Atur izin folder (penting untuk upload selfie)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
