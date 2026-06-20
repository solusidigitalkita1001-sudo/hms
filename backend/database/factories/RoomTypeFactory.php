<?php

namespace Database\Factories;

use App\Domain\Room\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomTypeFactory extends Factory
{
    protected $model = RoomType::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->word().' Room',
            'capacity' => fake()->numberBetween(1, 4),
            'base_price' => fake()->randomFloat(2, 300000, 1500000),
            'weekend_price' => fake()->randomFloat(2, 350000, 1800000),
            'extra_bed_price' => fake()->randomFloat(2, 100000, 300000),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
