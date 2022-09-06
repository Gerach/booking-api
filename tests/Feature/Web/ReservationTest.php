<?php

namespace Tests\Feature\Web;

use App\Models\Vacancy;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Reservation;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservations_are_not_rendered_without_login(): void
    {
        $response = $this->get('/home');
        $response->assertRedirect('/login');
    }

    public function test_reservations_are_rendered_for_logged_in_user(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/home');
        $response->assertOk();
    }

    public function test_reservations_list_is_rendered(): void
    {
        $user = User::factory()->create();

        $reservation1 = Reservation::factory()->create([
            'user_id' => $user,
            'reserved_since' => '2022-09-03',
            'reserved_till' => '2022-09-05',
        ]);
        $reservation2 = Reservation::factory()->create([
            'user_id' => $user,
            'reserved_since' => '2022-09-07',
            'reserved_till' => '2022-09-10',
        ]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->has('reservations.data', 2)
            ->has(
                'reservations.data.0', fn (AssertableInertia $page) => $page
                    ->where('id', $reservation1->id)
                    ->where('reservedSince', '2022-09-03')
                    ->where('reservedTill', '2022-09-05')
            )
            ->has(
                'reservations.data.1', fn (AssertableInertia $page) => $page
                ->where('id', $reservation2->id)
                ->where('reservedSince', '2022-09-07')
                ->where('reservedTill', '2022-09-10')
            )
        );
    }

    public function test_reservation_can_be_made(): void
    {
        $this->withoutMiddleware();
        $user = User::factory()->create();

        $reservationSince = (new CarbonImmutable())->modify('+2 days')->format('Y-m-d');
        $reservationMiddle = (new CarbonImmutable())->modify('+3 day')->format('Y-m-d');
        $reservationTill = (new CarbonImmutable())->modify('+4 days')->format('Y-m-d');
        $response = $this->actingAs($user)->post('/reservation', [
            'reservedSince' => $reservationSince,
            'reservedTill' => $reservationTill,
        ]);

        $response->assertRedirect('/home');
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

        $nonReservationDate1 = (new CarbonImmutable())->modify('+1 day')->format('Y-m-d');
        $nonReservationDate2 = (new CarbonImmutable())->modify('+5 days')->format('Y-m-d');
        $this->assertDatabaseMissing(Vacancy::class, [
            'vacancy_date' => $nonReservationDate1,
        ]);
        $this->assertDatabaseMissing(Vacancy::class, [
            'vacancy_date' => $nonReservationDate2,
        ]);
    }

    public function test_reservation_can_be_cancelled(): void
    {
        $this->withoutMiddleware();
        $user = User::factory()->create();

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

        $response = $this->actingAs($user)->delete("/reservation/$reservation->id");

        $response->assertRedirect('/home');
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
}
