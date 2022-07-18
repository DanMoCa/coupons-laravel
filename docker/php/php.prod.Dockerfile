FROM php:8.1.8-fpm-alpine

RUN mkdir -p /var/www/html

ADD . /var/www/html

RUN apk --no-cache add shadow && usermod -u 1000 www-data

RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS} \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev ${PHPIZE_DEPS}

RUN docker-php-ext-install pdo pdo_mysql