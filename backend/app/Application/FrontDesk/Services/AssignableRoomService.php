<?php

namespace App\Application\FrontDesk\Services;

use App\Domain\Reservation\Models\Reservation;
use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\OccupancyStatus;
use App\Domain\Room\Enums\ServiceabilityStatus;
use App\Domain\Room\Models\Room;
use Illuminate\Support\Collection;

class AssignableRoomService
{
    public function listForReservation(Reservation $reservation): Collection
    {
        $rooms = Room::query()
            ->with('roomType:id,code,name')
            ->where('property_id', $reservation->property_id)
            ->where('is_active', true)
            ->where('serviceability_status', ServiceabilityStatus::Normal)
            ->whereIn('housekeeping_status', [HousekeepingStatus::Clean, HousekeepingStatus::Inspected])
            ->where(function ($query) use ($reservation): void {
                $query
                    ->whereIn('current_status', [OccupancyStatus::Available, OccupancyStatus::Reserved]);

                if ($reservation->assigned_room_id) {
                    $query->orWhere('id', $reservation->assigned_room_id);
                }
            })
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get();

        return $rooms
            ->sortBy([
                fn (Room $room) => $room->room_type_id === $reservation->room_type_id ? 0 : 1,
                fn (Room $room) => $room->floor,
                fn (Room $room) => $room->room_number,
            ])
            ->values();
    }
}
