<?php

namespace Database\Factories;

use App\Domain\Property\Models\NightAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NightAuditFactory extends Factory
{
    protected $model = NightAudit::class;

    public function definition(): array
    {
        return [
            'business_date' => now()->subDay()->toDateString(),
            'next_business_date' => now()->toDateString(),
            'status' => 'completed',
            'closed_by_user_id' => User::factory(),
            'started_at' => now()->subHours(8),
            'completed_at' => now()->subHours(7),
            'notes' => 'Night audit completed successfully.',
            'summary_json' => [
                'occupancy_rate' => fake()->numberBetween(40, 100),
                'total_revenue' => fake()->numberBetween(5000000, 50000000),
                'total_departures' => fake()->numberBetween(0, 10),
                'total_arrivals' => fake()->numberBetween(0, 10),
                'total_in_house' => fake()->numberBetween(3, 15),
            ],
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'started_at' => now(),
            'completed_at' => null,
            'summary_json' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
            'closed_by_user_id' => null,
            'summary_json' => null,
        ]);
    }
}
