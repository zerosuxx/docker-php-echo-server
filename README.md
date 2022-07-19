# docker-php-echo-server

## usage
```shell
$ docker-compose up -d
$ curl -H "Debug: 1" http://localhost:18080
$ curl -H http://localhost:18080/status/random
$ curl -H http://localhost:18080/status/401
$ curl -H http://localhost:18080/redirect/https://google.com
```

## build
```shell
$ docker buildx build --push --platform=linux/arm64,linux/amd64 -t zerosuxx/php-echo-server .
```
