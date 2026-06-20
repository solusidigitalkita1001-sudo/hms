<?php

namespace Database\Factories;

use App\Domain\Reservation\Models\StayRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class StayRecordFactory extends Factory
{
    protected $model = StayRecord::class;

    public function definition(): array
    {
        return [
            'stay_status' => 'in_house',
            'actual_check_in_at' => now(),
            'expected_check_out_at' => now()->addDay()->setTime(12, 0),
            'primary_guest_name_snapshot' => fake()->name(),
        ];
    }
}
