<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\AssetLoan;
use App\Domain\Inventory\Models\LoanableAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetLoanFactory extends Factory
{
    protected $model = AssetLoan::class;

    public function definition(): array
    {
        return [
            'asset_id' => LoanableAsset::factory(),
            'staff_id' => User::factory(),
            'loaned_at' => now()->subHours(fake()->numberBetween(1, 12)),
            'returned_at' => null,
            'return_condition' => null,
            'charge_amount' => null,
            'notes' => null,
        ];
    }

    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'returned_at' => now(),
            'return_condition' => fake()->randomElement(['good', 'damaged', 'lost']),
            'charge_amount' => fn (array $attrs) => $attrs['return_condition'] === 'good' ? null : fake()->numberBetween(50000, 500000),
            'notes' => 'Returned in good condition.',
        ]);
    }
}
