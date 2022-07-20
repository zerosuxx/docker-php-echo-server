FROM php:8.1-alpine AS base

RUN apk add --no-cache libpq libstdc++

RUN adduser --disabled-password --gecos "" app

COPY --from=phpswoole/swoole:php8.1-alpine /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=phpswoole/swoole:php8.1-alpine /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

USER app

COPY ./src /home/app/src
COPY ./bin/server /home/app/bin/server

EXPOSE 8080

CMD ["-f", "/home/app/bin/server"]
