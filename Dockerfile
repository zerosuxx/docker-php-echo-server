FROM php:8.1-alpine AS base

RUN adduser --disabled-password --gecos "" app

FROM base AS builder

RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS}

RUN pecl install swoole

RUN docker-php-ext-enable swoole

FROM base AS app

RUN apk add --no-cache libstdc++

COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

USER app

COPY ./src /home/app/src
COPY ./bin/server /home/app/bin/server

EXPOSE 8080

CMD ["-f", "/home/app/bin/server"]
