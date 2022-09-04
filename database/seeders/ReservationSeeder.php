<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

class ReservationSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $user = User::factory()->create();

        Reservation::factory()->create([
            'user_id' => $user,
            'reserved_since' => Date::create(2022, 9, 3),
            'reserved_till' => Date::create(2022, 9, 4),
        ]);
        Reservation::factory()->create([
            'user_id' => $user,
            'reserved_since' => Date::create(2022, 9, 7),
            'reserved_till' => Date::create(2022, 9, 8),
        ]);
        Reservation::factory()->create([
            'user_id' => $user,
            'reserved_since' => Date::create(2022, 9, 3),
            'reserved_till' => Date::create(2022, 9, 9),
        ]);

        foreach ([3, 4, 7, 8] as $day) {
            Vacancy::factory()->create([
                'vacancy_date' => Date::create(2022, 9, $day),
                'remaining_vacancies' => 8,
            ]);
        }
        foreach ([5, 6, 9] as $day) {
            Vacancy::factory()->create([
                'vacancy_date' => Date::create(2022, 9, $day),
                'remaining_vacancies' => 9,
            ]);
        }
    }
}
