FROM php:8.2-apache

# Matikan modul yang bikin bentrok dan aktifkan mesin server yang stabil
RUN a2dismod mpm_event || true && a2enmod mpm_prefork

# Instal ekstensi MySQL agar file config.php bisa konek
RUN docker-php-ext-install pdo pdo_mysql

# Salin semua file proyek kamu
COPY . /var/www/html/

# Berikan izin akses folder agar bisa upload foto selfie
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80
