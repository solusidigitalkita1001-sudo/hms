<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\LoanableAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanableAssetFactory extends Factory
{
    protected $model = LoanableAsset::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Remote TV', 'Adaptor', 'Bantal Extra', 'Selimut Extra',
                'Setrika', 'Hair Dryer', 'Payung', 'Charger HP',
            ]),
            'description' => fake()->sentence(),
            'total_stock' => fake()->numberBetween(3, 20),
            'available_stock' => fn (array $attrs) => $attrs['total_stock'],
            'condition_notes' => fake()->randomElement(['Baik', 'Baru', 'Perlu perawatan']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
