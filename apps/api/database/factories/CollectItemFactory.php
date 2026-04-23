<?php

namespace Database\Factories;

use App\Models\Collect;
use App\Models\Trashbag;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectItemFactory extends Factory
{
    protected $model = CollectItem::class;

    public function definition(): array
    {
        return [
            'collect_id' => Collect::factory(),
            'trashbag_id' => Trashbag::factory(),
            'quantity' => $this->faker->numberBetween(1, 50),
        ];
    }
}
