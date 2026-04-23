<?php

namespace Database\Factories;

use App\Models\Sort;
use App\Models\Waste;
use Illuminate\Database\Eloquent\Factories\Factory;

class SortItemFactory extends Factory
{
    protected $model = SortItem::class;

    public function definition(): array
    {
        return [
            'sort_id' => Sort::factory(),
            'waste_id' => Waste::factory(),
            'weight' => $this->faker->randomFloat(2, 0.1, 500),
        ];
    }
}
