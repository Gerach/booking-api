FROM php:8.1.10-fpm

RUN docker-php-ext-install opcache && \
    docker-php-ext-install pdo_mysql

WORKDIR /var/www
