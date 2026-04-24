FROM php:8.2-fpm-alpine

ENV CFLAGS="-Wno-incompatible-pointer-types -Wno-int-conversion"
ENV CXXFLAGS="-Wno-incompatible-pointer-types -Wno-int-conversion"

RUN apk add --no-cache \
        git \
        unzip \
        bash \
        icu-dev \
        oniguruma-dev \
        libzip-dev \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        linux-headers \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        intl \
        opcache \
        zip \
    && pecl install psr \
    && pecl install phalcon-5.10.0 \
    && docker-php-ext-enable psr phalcon \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm", "-F"]
