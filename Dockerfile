FROM php:8.1-alpine

RUN adduser --disabled-password app

USER app

COPY ./server.php /home/app/server.php

EXPOSE 8080

CMD ["-S", "0.0.0.0:8080", "/home/app/server.php"]
