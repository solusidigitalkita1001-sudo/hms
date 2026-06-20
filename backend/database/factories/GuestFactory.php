<?php

namespace Database\Factories;

use App\Domain\Guest\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuestFactory extends Factory
{
    protected $model = Guest::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->email(),
            'id_type' => fake()->randomElement(['KTP', 'Passport', 'SIM']),
            'id_number' => fake()->numerify('################'),
            'address' => fake()->address(),
            'total_stays' => fake()->numberBetween(0, 10),
        ];
    }
}
