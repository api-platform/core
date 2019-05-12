FROM php:7.3-fpm-alpine

RUN apk update && apk add autoconf openssl-dev g++ make openssl mongodb && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb && \
    apk del --purge autoconf openssl-dev g++ make

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
    pecl install \
            xdebug \
       && rm -rf /tmp/pear \
       && docker-php-ext-enable xdebug && \
    apk del .build-deps && \
    sed -i '1 a xdebug.remote_enable=1' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    sed -i '1 a xdebug.remote_log=/srv/php/tests/Fixtures/app/var/log/xdebug_remote.log' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini;

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

VOLUME /data/db

EXPOSE 27017
