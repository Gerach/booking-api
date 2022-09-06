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

        $reservation1Since = (new CarbonImmutable())->modify('+2 days')->format('Y-m-d');
        $reservation1Till = (new CarbonImmutable())->modify('+3 days')->format('Y-m-d');
        $reservation2Since = (new CarbonImmutable())->modify('+5 days')->format('Y-m-d');
        $reservation2Till = (new CarbonImmutable())->modify('+9 days')->format('Y-m-d');
        $reservation1 = Reservation::factory()->create([
            'user_id' => $user,
            'reserved_since' => $reservation1Since,
            'reserved_till' => $reservation1Till,
        ]);
        $reservation2 = Reservation::factory()->create([
            'user_id' => $user,
            'reserved_since' => $reservation2Since,
            'reserved_till' => $reservation2Till,
        ]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->has('reservations.data', 2)
            ->has(
                'reservations.data.0', fn (AssertableInertia $page) => $page
                    ->where('id', $reservation1->id)
                    ->where('reservedSince', $reservation1Since)
                    ->where('reservedTill', $reservation1Till)
            )
            ->has(
                'reservations.data.1', fn (AssertableInertia $page) => $page
                ->where('id', $reservation2->id)
                ->where('reservedSince', $reservation2Since)
                ->where('reservedTill', $reservation2Till)
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

        $reservationSince = (new CarbonImmutable())->modify('+2 days')->format('Y-m-d');
        $reservationTill = (new CarbonImmutable())->modify('+3 days')->format('Y-m-d');
        $reservation = Reservation::factory()->create([
            'user_id' => $user,
            'reserved_since' => $reservationSince,
            'reserved_till' => $reservationTill,
        ]);
        $vacancy1 = Vacancy::factory()->create([
            'vacancy_date' => $reservationSince,
            'remaining_vacancies' => 9,
        ]);
        $vacancy2 = Vacancy::factory()->create([
            'vacancy_date' => $reservationTill,
            'remaining_vacancies' => 9,
        ]);

        $response = $this->actingAs($user)->delete("/reservation/$reservation->id");

        $response->assertRedirect('/home');
        $this->assertDatabaseMissing(Reservation::class, [
            'reserved_since' => $reservationSince,
            'reserved_till' => $reservationTill,
        ]);
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
