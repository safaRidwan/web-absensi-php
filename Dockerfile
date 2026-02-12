FROM php:8.2-apache

# Instal ekstensi untuk MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Salin semua file proyek ke server
COPY . /var/www/html/

# Atur izin folder agar tidak error saat upload selfie
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Port standar Railway
EXPOSE 80
