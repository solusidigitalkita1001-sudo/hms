<?php

namespace App\Application\FrontDesk\Services;

use App\Domain\Reservation\Models\Reservation;
use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\OccupancyStatus;
use App\Domain\Room\Enums\ServiceabilityStatus;
use App\Domain\Room\Models\Room;
use App\Domain\Room\Models\RoomAvailabilityLock;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AvailabilityService
{
    /**
     * Check if room is available for given dates
     */
    public function isRoomAvailable(int $roomId, Carbon $checkIn, Carbon $checkOut): bool
    {
        // Check room status
        $room = Room::find($roomId);
        if (! $room || ! $room->is_active) {
            return false;
        }

        if (! $room->isSellable()) {
            return false;
        }

        // Check for existing reservations that overlap
        $hasOverlap = Reservation::query()
            ->where('assigned_room_id', $roomId)
            ->where('reservation_status', '!=', 'cancelled')
            ->where('reservation_status', '!=', 'no_show')
            ->where('reservation_status', '!=', 'expired')
            ->where(function (Builder $q) use ($checkIn, $checkOut) {
                $q->where(function (Builder $q) use ($checkIn, $checkOut) {
                    // Reservation starts during requested period
                    $q->where('check_in_date', '>=', $checkIn->toDateString())
                        ->where('check_in_date', '<', $checkOut->toDateString());
                })->orWhere(function (Builder $q) use ($checkIn, $checkOut) {
                    // Reservation ends during requested period
                    $q->where('check_out_date', '>', $checkIn->toDateString())
                        ->where('check_out_date', '<=', $checkOut->toDateString());
                })->orWhere(function (Builder $q) use ($checkIn, $checkOut) {
                    // Reservation spans the entire requested period
                    $q->where('check_in_date', '<=', $checkIn->toDateString())
                        ->where('check_out_date', '>=', $checkOut->toDateString());
                });
            })
            ->exists();

        if ($hasOverlap) {
            return false;
        }

        // Check for availability locks
        $hasLock = RoomAvailabilityLock::query()
            ->where('room_id', $roomId)
            ->whereNull('released_at')
            ->where(function (Builder $q) use ($checkIn, $checkOut) {
                $q->where(function (Builder $q) use ($checkIn, $checkOut) {
                    $q->where('lock_start_date', '<=', $checkOut->toDateString())
                        ->where('lock_end_date', '>=', $checkIn->toDateString());
                });
            })
            ->exists();

        return ! $hasLock;
    }

    /**
     * Check if a room type has any available room for the given dates.
     */
    public function isRoomTypeAvailable(int $roomTypeId, ?int $propertyId, Carbon $checkIn, Carbon $checkOut): bool
    {
        return Room::query()
            ->sellable()
            ->where('room_type_id', $roomTypeId)
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            ->whereDoesntHave('reservations', function (Builder $q) use ($checkIn, $checkOut) {
                $q->whereNotIn('reservation_status', ['cancelled', 'no_show', 'expired'])
                    ->where(function (Builder $q) use ($checkIn, $checkOut) {
                        $q->whereBetween('check_in_date', [$checkIn->toDateString(), $checkOut->toDateString()])
                          ->orWhereBetween('check_out_date', [$checkIn->toDateString(), $checkOut->toDateString()])
                          ->orWhere(function (Builder $q) use ($checkIn, $checkOut) {
                              $q->where('check_in_date', '<=', $checkIn->toDateString())
                                ->where('check_out_date', '>=', $checkOut->toDateString());
                          });
                    });
            })
            ->whereDoesntHave('availabilityLocks', function (Builder $q) use ($checkIn, $checkOut) {
                $q->whereNull('released_at')
                    ->where(function (Builder $q) use ($checkIn, $checkOut) {
                        $q->where('lock_start_date', '<=', $checkOut->toDateString())
                            ->where('lock_end_date', '>=', $checkIn->toDateString());
                    });
            })
            ->exists();
    }

    /**
     * Find available room for room type
     */
    public function findAvailableRoom(int $roomTypeId, ?int $propertyId, Carbon $checkIn, Carbon $checkOut): ?Room
    {
        return Room::query()
            ->where('room_type_id', $roomTypeId)
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            ->where('is_active', true)
            ->sellable()
            ->whereDoesntHave('reservations', function (Builder $q) use ($checkIn, $checkOut) {
                $q->where('reservation_status', '!=', 'cancelled')
                    ->where('reservation_status', '!=', 'no_show')
                    ->where('reservation_status', '!=', 'expired')
                    ->where(function (Builder $q) use ($checkIn, $checkOut) {
                        $q->where(function (Builder $q) use ($checkIn, $checkOut) {
                            $q->where('check_in_date', '>=', $checkIn->toDateString())
                                ->where('check_in_date', '<', $checkOut->toDateString());
                        })->orWhere(function (Builder $q) use ($checkIn, $checkOut) {
                            $q->where('check_out_date', '>', $checkIn->toDateString())
                                ->where('check_out_date', '<=', $checkOut->toDateString());
                        })->orWhere(function (Builder $q) use ($checkIn, $checkOut) {
                            $q->where('check_in_date', '<=', $checkIn->toDateString())
                                ->where('check_out_date', '>=', $checkOut->toDateString());
                        });
                    });
            })
            ->whereDoesntHave('availabilityLocks', function (Builder $q) use ($checkIn, $checkOut) {
                $q->whereNull('released_at')
                    ->where(function (Builder $q) use ($checkIn, $checkOut) {
                        $q->where('lock_start_date', '<=', $checkOut->toDateString())
                            ->where('lock_end_date', '>=', $checkIn->toDateString());
                    });
            })
            ->orderBy('room_number')
            ->first();
    }

    /**
     * Get available rooms for room type
     */
    public function getAvailableRooms(int $roomTypeId, ?int $propertyId, Carbon $checkIn, Carbon $checkOut): \Illuminate\Support\Collection
    {
        return Room::query()
            ->with('roomType')
            ->where('room_type_id', $roomTypeId)
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            ->where('is_active', true)
            ->sellable()
            ->whereDoesntHave('reservations', function (Builder $q) use ($checkIn, $checkOut) {
                $q->where('reservation_status', '!=', 'cancelled')
                    ->where('reservation_status', '!=', 'no_show')
                    ->where('reservation_status', '!=', 'expired')
                    ->where(function (Builder $q) use ($checkIn, $checkOut) {
                        $q->where(function (Builder $q) use ($checkIn, $checkOut) {
                            $q->where('check_in_date', '>=', $checkIn->toDateString())
                                ->where('check_in_date', '<', $checkOut->toDateString());
                        })->orWhere(function (Builder $q) use ($checkIn, $checkOut) {
                            $q->where('check_out_date', '>', $checkIn->toDateString())
                                ->where('check_out_date', '<=', $checkOut->toDateString());
                        })->orWhere(function (Builder $q) use ($checkIn, $checkOut) {
                            $q->where('check_in_date', '<=', $checkIn->toDateString())
                                ->where('check_out_date', '>=', $checkOut->toDateString());
                        });
                    });
            })
            ->whereDoesntHave('availabilityLocks', function (Builder $q) use ($checkIn, $checkOut) {
                $q->whereNull('released_at')
                    ->where(function (Builder $q) use ($checkIn, $checkOut) {
                        $q->where('lock_start_date', '<=', $checkOut->toDateString())
                            ->where('lock_end_date', '>=', $checkIn->toDateString());
                    });
            })
            ->orderBy('room_number')
            ->get();
    }
}