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
$ docker exec -it booking-api-php-cli-1 php artisan test
```

### WEB
Web gui available via http://localhost:8080. After performing registration it is possible to login to application.
Alternatively default created user "test@example.com:password" can be used.

### API

#### Login
```http
POST /api/login
```
Request parameters:

| Parameter  | Type     | Description                 |
|:-----------|:---------|:----------------------------|
| `email`    | `email`  | **Required**. User email    |
| `password` | `string` | **Required**. User password |

Response: User's authentication token

#### Fetch reservations:
```http
GET /api/v1/reservations
```
Request parameters: none
Response: List of reservations

#### Make reservation:
```http
POST /api/v1/reservations
```
Request parameters:

| Parameter       | Type   | Description                                                  |
|:----------------|:-------|:-------------------------------------------------------------|
| `reservedSince` | `date` | **Required**. Date since when to schedule reservation        |
| `reservedTill`  | `date` | **Required**. Date until which reservation will be scheduled |

Response: Created reservation

#### Cancel reservation:
```http
DELETE /api/v1/reservations/{reservationId}
```
Request parameters: reservationId is part of request uri
Response: none
