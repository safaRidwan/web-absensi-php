FROM php:8.2-apache

# Paksa hanya prefork aktif
RUN a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork

RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
