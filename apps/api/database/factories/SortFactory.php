<?php

namespace Database\Factories;

use App\Models\Collect;
use App\Models\Sort;
use Illuminate\Database\Eloquent\Factories\Factory;

class SortFactory extends Factory
{
    protected $model = Sort::class;

    public function definition(): array
    {
        return [
            'collect_id' => Collect::factory(),
            'code' => strtoupper($this->faker->unique()->bothify('SRT-####-????')),
        ];
    }
}
