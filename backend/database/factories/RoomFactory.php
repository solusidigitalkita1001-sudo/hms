<?php

namespace Database\Factories;

use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\OccupancyStatus;
use App\Domain\Room\Enums\ServiceabilityStatus;
use App\Domain\Room\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'room_number' => fake()->unique()->numberBetween(100, 999),
            'floor' => fake()->numberBetween(1, 10),
            'current_status' => OccupancyStatus::Available,
            'housekeeping_status' => HousekeepingStatus::Clean,
            'serviceability_status' => ServiceabilityStatus::Normal,
            'is_active' => true,
        ];
    }
}
