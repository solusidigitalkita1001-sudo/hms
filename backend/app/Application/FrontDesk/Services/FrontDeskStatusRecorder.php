<?php

namespace App\Application\FrontDesk\Services;

use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\ReservationStatusLog;
use App\Domain\Room\Models\Room;
use App\Domain\Room\Models\RoomStatusLog;

class FrontDeskStatusRecorder
{
    public function recordReservationTransition(
        Reservation $reservation,
        ?string $fromStatus,
        string $toStatus,
        ?int $actorUserId,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): void {
        if ($fromStatus === $toStatus) {
            return;
        }

        ReservationStatusLog::query()->create([
            'reservation_id' => $reservation->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_user_id' => $actorUserId,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'changed_at' => now(),
        ]);
    }

    public function recordRoomTransition(
        Room $room,
        string $statusDomain,
        ?string $fromStatus,
        string $toStatus,
        ?int $actorUserId,
        ?string $note = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): void {
        if ($fromStatus === $toStatus) {
            return;
        }

        RoomStatusLog::query()->create([
            'room_id' => $room->id,
            'status_domain' => $statusDomain,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_user_id' => $actorUserId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'changed_at' => now(),
            'note' => $note,
        ]);
    }
}
