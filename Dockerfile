FROM php:8.3-apache

RUN docker-php-ext-install mysqli

# passman/xss/ (attacker-side lab tooling) is excluded by .dockerignore
COPY passman/ /var/www/html/passman/
