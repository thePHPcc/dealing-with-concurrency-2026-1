FROM php:8.5-cli

RUN docker-php-ext-install pdo_mysql
RUN pecl install redis \
    && docker-php-ext-enable redis
