<?php

declare(strict_types=1);

namespace Tests\Unit\Api\V1;

use App\Models\Reservation;
use App\Models\User;
use App\Models\Vacancy;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

final class ReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login(): void
    {
        $user = User::factory()->create();
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk();
    }

    public function test_login_missing_email(): void
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password',
        ]);

        $expectedResponse = [
            'errors' => [
                'email' => ['The email field is required.'],
            ],
            'message' => 'The email field is required.'
        ];
        $response->assertUnprocessable();
        $response->assertExactJson($expectedResponse);
    }

    public function test_login_missing_password(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'email@email.com',
        ]);

        $expectedResponse = [
            'errors' => [
                'password' => ['The password field is required.'],
            ],
            'message' => 'The password field is required.'
        ];
        $response->assertUnprocessable();
        $response->assertExactJson($expectedResponse);
    }

    public function test_login_invalid_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'email',
            'password' => 'password',
        ]);

        $expectedResponse = [
            'errors' => [
                'email' => ['The email must be a valid email address.'],
            ],
            'message' => 'The email must be a valid email address.'
        ];
        $response->assertUnprocessable();
        $response->assertExactJson($expectedResponse);
    }

    public function test_login_invalid_credentials(): void
    {
        $user = User::factory()->create();
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'invalid_password',
        ]);

        $expectedResponse = [
            'errors' => [
                'email' => ['These credentials do not match our records.'],
            ],
            'message' => 'These credentials do not match our records.'
        ];
        $response->assertUnprocessable();
        $response->assertExactJson($expectedResponse);
    }

    public function test_index(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Reservation::factory()->create([
            'user_id' => $user1,
            'reserved_since' => Date::create(2022, 9, 6),
            'reserved_till' => Date::create(2022, 9, 7),
        ]);
        $reservation2 = Reservation::factory()->create([
            'user_id' => $user2,
            'reserved_since' => Date::create(2022, 9, 11),
            'reserved_till' => Date::create(2022, 9, 13),
        ]);
        $authorizationToken = $this->performValidLogin($user2);

        $response = $this->withHeader('Authorization', $authorizationToken)->getJson('/api/v1/reservations');

        $expectedResponse = [
            'data' => [[
                'id' => $reservation2->id,
                'reservedSince' => '2022-09-11',
                'reservedTill' => '2022-09-13',
            ]],
        ];
        $response->assertOk();
        $response->assertExactJson($expectedResponse);
    }

    public function test_index_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/reservations');
        $response->assertUnauthorized();
    }

    public function test_show(): void
    {
        $user = User::factory()->create();
        Reservation::factory()->create([
            'user_id' => $user,
            'reserved_since' => Date::create(2022, 9, 6),
            'reserved_till' => Date::create(2022, 9, 7),
        ]);
        $reservation2 = Reservation::factory()->create([
            'user_id' => $user,
            'reserved_since' => Date::create(2022, 9, 11),
            'reserved_till' => Date::create(2022, 9, 13),
        ]);
        $authorizationToken = $this->performValidLogin($user);

        $response = $this
            ->withHeader('Authorization', $authorizationToken)
            ->getJson("/api/v1/reservations/$reservation2->id");

        $expectedResponse = [
            'data' => [
                'id' => $reservation2->id,
                'reservedSince' => '2022-09-11',
                'reservedTill' => '2022-09-13',
            ],
        ];
        $response->assertOk();
        $response->assertExactJson($expectedResponse);
    }

    public function test_show_unauthenticated(): void
    {
        $user = User::factory()->create();
        Reservation::factory()->create([
            'user_id' => $user,
            'reserved_since' => Date::create(2022, 9, 6),
            'reserved_till' => Date::create(2022, 9, 7),
        ]);
        $reservation2 = Reservation::factory()->create([
            'user_id' => $user,
            'reserved_since' => Date::create(2022, 9, 11),
            'reserved_till' => Date::create(2022, 9, 13),
        ]);

        $response = $this->getJson("/api/v1/reservations/$reservation2->id");

        $response->assertUnauthorized();
    }

    public function test_show_non_existing(): void
    {
        $user = User::factory()->create();
        $authorizationToken = $this->performValidLogin($user);

        $response = $this
            ->withHeader('Authorization', $authorizationToken)
            ->getJson('/api/v1/reservations/777');

        $response->assertNotFound();
    }

    public function test_show_unauthorized(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Reservation::factory()->create([
            'user_id' => $user1,
            'reserved_since' => Date::create(2022, 9, 6),
            'reserved_till' => Date::create(2022, 9, 7),
        ]);
        $reservation2 = Reservation::factory()->create([
            'user_id' => $user2,
            'reserved_since' => Date::create(2022, 9, 11),
            'reserved_till' => Date::create(2022, 9, 13),
        ]);
        $authorizationToken = $this->performValidLogin($user1);

        $response = $this
            ->withHeader('Authorization', $authorizationToken)
            ->getJson("/api/v1/reservations/$reservation2->id");

        $response->assertUnauthorized();
    }

    public function test_store(): void
    {
        $user = User::factory()->create();
        $authorizationToken = $this->performValidLogin($user);

        $reservationSince = (new CarbonImmutable())->modify('+2 days')->format('Y-m-d');
        $reservationMiddle = (new CarbonImmutable())->modify('+3 days')->format('Y-m-d');
        $reservationTill = (new CarbonImmutable())->modify('+4 days')->format('Y-m-d');
        $response = $this->withHeader('Authorization', $authorizationToken)->postJson('/api/v1/reservations', [
            'reservedSince' => $reservationSince,
            'reservedTill' => $reservationTill,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas(Reservation::class, [
            'reserved_since' => $reservationSince,
            'reserved_till' => $reservationTill,
        ]);
        $this->assertDatabaseHas(Vacancy::class, [
            'vacancy_date' => $reservationSince,
            'remaining_vacancies' => 9,
        ]);
        $this->assertDatabaseHas(Vacancy::class, [
            'vacancy_date' => $reservationMiddle,
            'remaining_vacancies' => 9,
        ]);
        $this->assertDatabaseHas(Vacancy::class, [
            'vacancy_date' => $reservationTill,
            'remaining_vacancies' => 9,
        ]);

        $nonReservationDay1 = (new CarbonImmutable())->modify('+1 days')->format('Y-m-d');
        $nonReservationDay2 = (new CarbonImmutable())->modify('+5 days')->format('Y-m-d');
        $this->assertDatabaseMissing(Vacancy::class, [
            'vacancy_date' => $nonReservationDay1,
        ]);
        $this->assertDatabaseMissing(Vacancy::class, [
            'vacancy_date' => $nonReservationDay2,
        ]);
    }

    public function test_store_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/reservations', [
            'reservedSince' => '2022-09-06',
            'reservedTill' => '2022-09-08',
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_missing_reserved_since(): void
    {
        $user = User::factory()->create();
        $authorizationToken = $this->performValidLogin($user);

        $reservationTill = (new CarbonImmutable())->modify('+2 days')->format('Y-m-d');
        $response = $this->withHeader('Authorization', $authorizationToken)->postJson('/api/v1/reservations', [
            'reservedTill' => $reservationTill,
        ]);

        $expectedResponse = [
            'message' => 'The reserved since field is required.',
            'errors' => [
                'reservedSince' => [
                    'The reserved since field is required.',
                ]
            ]
        ];
        $response->assertUnprocessable();
        $response->assertExactJson($expectedResponse);
    }

    public function test_store_missing_reserved_till(): void
    {
        $user = User::factory()->create();
        $authorizationToken = $this->performValidLogin($user);

        $reservedSince = (new CarbonImmutable())->modify('+2 days')->format('Y-m-d');
        $response = $this->withHeader('Authorization', $authorizationToken)->postJson('/api/v1/reservations', [
            'reservedSince' => $reservedSince,
        ]);

        $expectedResponse = [
            'message' => 'The reserved since must be a date before or equal to reserved till. (and 1 more error)',
            'errors' => [
                'reservedSince' => [
                    'The reserved since must be a date before or equal to reserved till.',
                ],
                'reservedTill' => [
                    'The reserved till field is required.',
                ],
            ]
        ];
        $response->assertUnprocessable();
        $response->assertExactJson($expectedResponse);
    }

    public function test_store_invalid_reserved_since(): void
    {
        $user = User::factory()->create();
        $authorizationToken = $this->performValidLogin($user);

        $reservedTill = (new CarbonImmutable())->modify('+2 days')->format('Y-m-d');
        $response = $this->withHeader('Authorization', $authorizationToken)->postJson('/api/v1/reservations', [
            'reservedSince' => '2022-09-063',
            'reservedTill' => $reservedTill,
        ]);

        $expectedResponse = [
            'message' => 'The reserved since is not a valid date. (and 1 more error)',
            'errors' => [
                'reservedSince' => [
                    'The reserved since is not a valid date.',
                    'The reserved since must be a date after or equal to today.',
                ],
            ]
        ];
        $response->assertUnprocessable();
        $response->assertExactJson($expectedResponse);
    }

    public function test_store_invalid_reserved_till(): void
    {
        $user = User::factory()->create();
        $authorizationToken = $this->performValidLogin($user);

        $reservedSince = (new CarbonImmutable())->modify('+2 days')->format('Y-m-d');
        $response = $this->withHeader('Authorization', $authorizationToken)->postJson('/api/v1/reservations', [
            'reservedSince' => $reservedSince,
            'reservedTill' => '2022-09-083',
        ]);

        $expectedResponse = [
            'message' => 'The reserved since must be a date before or equal to reserved till. (and 2 more errors)',
            'errors' => [
                'reservedSince' => [
                    'The reserved since must be a date before or equal to reserved till.',
                ],
                'reservedTill' => [
                    'The reserved till is not a valid date.',
                    'The reserved till must be a date after or equal to today.',
                ],
            ]
        ];
        $response->assertUnprocessable();
        $response->assertExactJson($expectedResponse);
    }

    public function test_store_since_later_than_till(): void
    {
        $user = User::factory()->create();
        $authorizationToken = $this->performValidLogin($user);

        $reservedSince = (new CarbonImmutable())->modify('+3 days')->format('Y-m-d');
        $reservedTill = (new CarbonImmutable())->modify('+2 days')->format('Y-m-d');
        $response = $this->withHeader('Authorization', $authorizationToken)->postJson('/api/v1/reservations', [
            'reservedSince' => $reservedSince,
            'reservedTill' => $reservedTill,
        ]);

        $expectedResponse = [
            'message' => 'The reserved since must be a date before or equal to reserved till.',
            'errors' => [
                'reservedSince' => [
                    'The reserved since must be a date before or equal to reserved till.',
                ],
            ]
        ];
        $response->assertUnprocessable();
        $response->assertExactJson($expectedResponse);
    }

    public function test_store_reserved_since_in_the_past(): void
    {
        $user = User::factory()->create();
        $authorizationToken = $this->performValidLogin($user);

        $reservedSince = (new CarbonImmutable())->modify('-1 day')->format('Y-m-d');
        $reservedTill = (new CarbonImmutable())->modify('+2 days')->format('Y-m-d');
        $response = $this->withHeader('Authorization', $authorizationToken)->postJson('/api/v1/reservations', [
            'reservedSince' => $reservedSince,
            'reservedTill' => $reservedTill,
        ]);

        $expectedResponse = [
            'message' => 'The reserved since must be a date after or equal to today.',
            'errors' => [
                'reservedSince' => [
                    'The reserved since must be a date after or equal to today.',
                ],
            ]
        ];
        $response->assertUnprocessable();
        $response->assertExactJson($expectedResponse);
    }

    public function test_store_reserved_till_in_the_past(): void
    {
        $user = User::factory()->create();
        $authorizationToken = $this->performValidLogin($user);

        $reservedSince = (new CarbonImmutable())->modify('-3 day')->format('Y-m-d');
        $reservedTill = (new CarbonImmutable())->modify('-2 days')->format('Y-m-d');
        $response = $this->withHeader('Authorization', $authorizationToken)->postJson('/api/v1/reservations', [
            'reservedSince' => $reservedSince,
            'reservedTill' => $reservedTill,
        ]);

        $expectedResponse = [
            'message' => 'The reserved since must be a date after or equal to today. (and 1 more error)',
            'errors' => [
                'reservedSince' => [
                    'The reserved since must be a date after or equal to today.',
                ],
                'reservedTill' => [
                    'The reserved till must be a date after or equal to today.',
                ],
            ]
        ];
        $response->assertUnprocessable();
        $response->assertExactJson($expectedResponse);
    }

    public function test_destroy(): void
    {
        $user = User::factory()->create();
        $authorizationToken = $this->performValidLogin($user);

        $reservation = Reservation::factory()->create([
            'user_id' => $user,
            'reserved_since' => '2022-09-01',
            'reserved_till' => '2022-09-02',
        ]);
        $vacancy1 = Vacancy::factory()->create([
            'vacancy_date' => '2022-09-01',
            'remaining_vacancies' => 9,
        ]);
        $vacancy2 = Vacancy::factory()->create([
            'vacancy_date' => '2022-09-02',
            'remaining_vacancies' => 9,
        ]);

        $response = $this
            ->withHeader('Authorization', $authorizationToken)
            ->deleteJson("/api/v1/reservations/$reservation->id");

        $response->assertOk();
        $this->assertDatabaseMissing(Reservation::class, ['id' => $reservation->id]);
        $this->assertDatabaseHas(Vacancy::class, [
            'id' => $vacancy1->id,
            'remaining_vacancies' => 10,
        ]);
        $this->assertDatabaseHas(Vacancy::class, [
            'id' => $vacancy2->id,
            'remaining_vacancies' => 10,
        ]);
    }

    public function test_destroy_unauthorized(): void
    {
        $user = User::factory()->create();
        $loggedInUser = User::factory()->create();
        $authorizationToken = $this->performValidLogin($loggedInUser);

        $reservation = Reservation::factory()->create([
            'user_id' => $user,
            'reserved_since' => '2022-09-01',
            'reserved_till' => '2022-09-02',
        ]);
        $vacancy1 = Vacancy::factory()->create([
            'vacancy_date' => '2022-09-01',
            'remaining_vacancies' => 9,
        ]);
        $vacancy2 = Vacancy::factory()->create([
            'vacancy_date' => '2022-09-02',
            'remaining_vacancies' => 9,
        ]);

        $response = $this
            ->withHeader('Authorization', $authorizationToken)
            ->deleteJson("/api/v1/reservations/$reservation->id");

        $response->assertUnauthorized();
        $this->assertDatabaseHas(Reservation::class, ['id' => $reservation->id]);
        $this->assertDatabaseHas(Vacancy::class, [
            'id' => $vacancy1->id,
            'remaining_vacancies' => 9,
        ]);
        $this->assertDatabaseHas(Vacancy::class, [
            'id' => $vacancy2->id,
            'remaining_vacancies' => 9,
        ]);
    }

    public function test_destroy_non_existent(): void
    {
        $user = User::factory()->create();
        $authorizationToken = $this->performValidLogin($user);

        $response = $this
            ->withHeader('Authorization', $authorizationToken)
            ->deleteJson("/api/v1/reservations/777");

        $response->assertNotFound();
    }

    private function performValidLogin(User $user): string
    {
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        return 'Bearer ' . $response->decodeResponseJson()->json();
    }
}
