FROM php:8.3-apache

RUN docker-php-ext-install mysqli

COPY passman/ /var/www/html/passman/
RUN chown www-data:www-data /var/www/html/passman/xss/stolencookies.txt
