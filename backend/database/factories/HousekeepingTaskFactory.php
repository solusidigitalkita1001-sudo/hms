<?php

namespace Database\Factories;

use App\Domain\Housekeeping\Models\HousekeepingTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HousekeepingTaskFactory extends Factory
{
    protected $model = HousekeepingTask::class;

    public function definition(): array
    {
        return [
            'task_type' => fake()->randomElement(['checkout_cleaning', 'daily_cleaning', 'deep_clean', 'maintenance_check']),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'task_status' => 'pending',
            'scheduled_for' => now()->addHours(fake()->numberBetween(0, 4)),
            'issue_note' => fake()->optional()->sentence(),
            'created_by_user_id' => User::factory(),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'task_status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'task_status' => 'completed',
            'started_at' => now()->subHours(2),
            'completed_at' => now(),
            'verified_by_user_id' => User::factory(),
            'verified_at' => now(),
        ]);
    }
}
