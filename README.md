# booking-api

### Prerequisites
* Docker version 20.10.17
* Ports 8080 and 13306 not used by another application

### Commands

List of commands to operate this project
* Launch project:
```sh
$ docker compose up -d
$ docker exec -it booking-api-php-cli-1 composer i
$ docker exec -it booking-api-node-1 npm i && npm run build
$ docker exec -it booking-api-php-cli-1 php artisan migrate --seed
```
* Stop project:
```sh
$ docker compose stop
```
* Launch tests:
```sh
$ docker exec -it booking-api-php-cli-1 php artisan test --parallel
```
