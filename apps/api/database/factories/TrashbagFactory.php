<?php

namespace Database\Factories;

use App\Models\Trashbag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrashbagFactory extends Factory
{
    protected $model = Trashbag::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true) . ' Bag',
        ];
    }
}
