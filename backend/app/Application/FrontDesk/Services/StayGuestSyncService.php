<?php

namespace App\Application\FrontDesk\Services;

use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\StayRecord;

class StayGuestSyncService
{
    public function syncFromReservation(StayRecord $stayRecord, Reservation $reservation): void
    {
        $reservation->loadMissing(['primaryGuest', 'reservationGuests.guest']);

        $rows = [];

        if ($reservation->primary_guest_id) {
            $rows[$reservation->primary_guest_id] = [
                'guest_id' => $reservation->primary_guest_id,
                'is_primary' => true,
                'occupancy_role' => 'primary',
                'identity_verified_at' => $reservation->primaryGuest?->identity_verified_at,
                'notes' => null,
            ];
        }

        foreach ($reservation->reservationGuests as $reservationGuest) {
            if (! $reservationGuest->guest_id) {
                continue;
            }

            $rows[$reservationGuest->guest_id] = [
                'guest_id' => $reservationGuest->guest_id,
                'is_primary' => (bool) $reservationGuest->is_primary,
                'occupancy_role' => $reservationGuest->guest_role ?: 'occupant',
                'identity_verified_at' => $reservationGuest->guest?->identity_verified_at,
                'notes' => $reservationGuest->notes,
            ];
        }

        $stayRecord->stayGuests()->delete();

        if ($rows === []) {
            return;
        }

        $stayRecord->stayGuests()->createMany(array_values(array_map(
            fn (array $row): array => $row + ['stay_record_id' => $stayRecord->id],
            $rows
        )));
    }
}
