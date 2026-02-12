FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

# Ubah Apache agar listen ke PORT dari Railway saat runtime
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf \
    && sed -i 's/:80/:${PORT}/g' /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 8080

CMD ["apache2-foreground"]
