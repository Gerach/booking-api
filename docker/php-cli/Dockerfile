FROM php:8.1.10-cli

#RUN apt-get update && \
#    apt-get install wget && \
#    apt-get clean

# install composer
COPY ./docker/php-cli/install-composer.sh /tmp/
RUN chmod +x /tmp/install-composer.sh && ./tmp/install-composer.sh && mv composer.phar /usr/local/bin/composer

WORKDIR /var/www
