<?php

namespace Database\Factories;

use App\Domain\Room\Models\RoomConditionReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomConditionReportFactory extends Factory
{
    protected $model = RoomConditionReport::class;

    public function definition(): array
    {
        return [
            'reporter_type' => 'guest',
            'guest_name' => fake()->name(),
            'report_time' => now()->subMinutes(fake()->numberBetween(5, 60)),
            'window_expired_at' => now()->addMinutes(fake()->numberBetween(10, 30)),
            'items' => [
                [
                    'category' => fake()->randomElement(['Kebersihan', 'Furniture', 'Elektronik', 'Plumbing']),
                    'description' => fake()->sentence(),
                ],
            ],
            'acknowledged_by' => null,
            'acknowledged_at' => null,
            'admin_notes' => null,
        ];
    }

    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'acknowledged_by' => User::factory(),
            'acknowledged_at' => now(),
            'admin_notes' => 'Noted.',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'window_expired_at' => now()->subHour(),
        ]);
    }

    public function fromStaff(): static
    {
        return $this->state(fn (array $attributes) => [
            'reporter_type' => 'staff',
            'guest_name' => null,
            'reported_by' => User::factory(),
            'window_expired_at' => null,
        ]);
    }
}
