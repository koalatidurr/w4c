<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'date' => $this->faker->dateTimeBetween('-4 years', '+1 year'),
        ];
    }
}
