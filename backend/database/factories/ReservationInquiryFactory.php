<?php

namespace Database\Factories;

use App\Domain\Reservation\Models\ReservationInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationInquiryFactory extends Factory
{
    protected $model = ReservationInquiry::class;

    public function definition(): array
    {
        return [
            'guest_name' => fake()->name(),
            'guest_email' => fake()->email(),
            'guest_phone' => fake()->phoneNumber(),
            'check_in_date' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'check_out_date' => fn (array $attrs) => \Carbon\Carbon::parse($attrs['check_in_date'])->addDays(fake()->numberBetween(1, 5))->toDateString(),
            'adult_count' => fake()->numberBetween(1, 4),
            'child_count' => fake()->numberBetween(0, 2),
            'message' => fake()->optional()->sentence(),
        ];
    }

    public function contacted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'contacted',
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'converted',
        ]);
    }
}
