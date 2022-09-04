<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $since = $this->faker->dateTimeThisMonth();
        $till = $since->modify('+1 day');

        return [
            'user_id' => User::factory(),
            'reserved_since' => $since,
            'reserved_till' => $till,
        ];
    }
}
