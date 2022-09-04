<?php

namespace Database\Factories;

use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vacancy>
 */
class VacancyFactory extends Factory
{
    protected $model = Vacancy::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vacancy_date' => $this->faker->dateTimeThisMonth(),
            'remaining_vacancies' => 10,
        ];
    }
}
