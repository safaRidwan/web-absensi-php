FROM php:8.2-apache

# Instal ekstensi untuk database MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Salin semua file proyek kamu ke server
COPY . /var/www/html/

# Atur izin folder agar foto selfie bisa tersimpan di folder uploads
RUN chown -R www-data:www-data /var/www/html/uploads && chmod -R 777 /var/www/html/uploads

# Aktifkan pengaturan Apache
RUN a2enmod rewrite

# Beritahu port yang digunakan
EXPOSE 80