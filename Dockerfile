FROM php:8.1-apache

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPORT 80

CMD ["apache2-foreground"]