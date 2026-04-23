<?php

namespace Database\Factories;

use App\Models\Collect;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectFactory extends Factory
{
    protected $model = Collect::class;

    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'code' => strtoupper($this->faker->unique()->bothify('CLT-####-????')),
            'status' => $this->faker->randomElement(['DONE', 'SKIP']),
        ];
    }
}
