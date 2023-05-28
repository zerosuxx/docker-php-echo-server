FROM php:8.2-alpine AS base

RUN apk add --no-cache \
    libpq \
    libstdc++ \
    openssl1.1-compat

RUN adduser \
    --disabled-password \
    --gecos "" \
    app

USER app

COPY --from=openswoole/swoole:php8.2-alpine /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=openswoole/swoole:php8.2-alpine /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

COPY ./src /home/app/src
COPY ./bin/server /home/app/bin/server

EXPOSE 8080

CMD ["-f", "/home/app/bin/server"]
