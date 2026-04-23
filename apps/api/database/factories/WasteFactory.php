<?php

namespace Database\Factories;

use App\Models\Waste;
use Illuminate\Database\Eloquent\Factories\Factory;

class WasteFactory extends Factory
{
    protected $model = Waste::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
        ];
    }
}
