<?php

namespace Database\Factories;

use App\Domain\Reservation\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'booking_code' => strtoupper('BK-'.fake()->unique()->bothify('########')),
            'source' => 'direct',
            'reservation_status' => 'confirmed',
            'adult_count' => fake()->numberBetween(1, 4),
            'child_count' => fake()->numberBetween(0, 2),
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'booked_at' => now(),
        ];
    }
}
