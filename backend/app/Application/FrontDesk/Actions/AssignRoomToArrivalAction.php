<?php

namespace App\Application\FrontDesk\Actions;

use App\Application\FrontDesk\Services\FrontDeskStatusRecorder;
use App\Domain\FrontDesk\Models\FrontDeskAuditLog;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\ReservationCheckinSession;
use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\OccupancyStatus;
use App\Domain\Room\Enums\ServiceabilityStatus;
use App\Domain\Room\Models\RoomAvailabilityLock;
use App\Domain\Room\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignRoomToArrivalAction
{
    public function __construct(
        private readonly FrontDeskStatusRecorder $statusRecorder,
    ) {}

    public function handle(Reservation $reservation, array $payload, int $actorUserId): Reservation
    {
        DB::transaction(function () use ($reservation, $payload, $actorUserId): void {
            $reservation->refresh()->loadMissing(['assignedRoom']);

            $room = Room::query()
                ->lockForUpdate()
                ->whereKey($payload['room_id'])
                ->firstOrFail();

            if ($reservation->property_id && $room->property_id !== $reservation->property_id) {
                throw ValidationException::withMessages([
                    'room_id' => 'Kamar tidak berada di properti yang sama dengan reservasi.',
                ]);
            }

            if (! $room->is_active || $room->serviceability_status->value !== ServiceabilityStatus::Normal->value) {
                throw ValidationException::withMessages([
                    'room_id' => 'Kamar tidak bisa diassign karena sedang tidak aktif atau diblokir.',
                ]);
            }

            if (! in_array($room->housekeeping_status->value, [HousekeepingStatus::Clean->value, HousekeepingStatus::Inspected->value], true)) {
                throw ValidationException::withMessages([
                    'room_id' => 'Kamar belum siap dipakai karena housekeeping belum release.',
                ]);
            }

            if (! in_array($room->current_status->value, [OccupancyStatus::Available->value, OccupancyStatus::Reserved->value], true)) {
                throw ValidationException::withMessages([
                    'room_id' => 'Kamar belum siap dipakai untuk assignment.',
                ]);
            }

            $previousReservationStatus = $reservation->reservation_status;
            $previousRoom = $reservation->assignedRoom;
            $checkedInFlowStatus = in_array($previousReservationStatus, ['reserved', 'arrival_due'], true)
                ? 'arrived'
                : $previousReservationStatus;

            if ($previousRoom && $previousRoom->id !== $room->id && ($previousRoom->current_status?->value ?? $previousRoom->current_status) === OccupancyStatus::Reserved->value) {
                $previousRoom->forceFill([
                    'current_status' => OccupancyStatus::Available,
                ])->save();

                $this->statusRecorder->recordRoomTransition(
                    $previousRoom,
                    'occupancy',
                    OccupancyStatus::Reserved->value,
                    OccupancyStatus::Available->value,
                    $actorUserId,
                    'Room released due to reassignment.',
                    Reservation::class,
                    $reservation->id,
                );
            }

            RoomAvailabilityLock::query()
                ->where('reservation_id', $reservation->id)
                ->whereNull('released_at')
                ->update([
                    'released_at' => now(),
                    'release_reason' => 'reassigned',
                    'updated_at' => now(),
                ]);

            $reservation->forceFill([
                'assigned_room_id' => $room->id,
                'reservation_status' => $checkedInFlowStatus,
                'arrived_at' => $reservation->arrived_at ?? now(),
            ])->save();

            $roomPreviousStatus = $room->current_status;
            $room->forceFill([
                'current_status' => OccupancyStatus::Reserved,
            ])->save();

            ReservationCheckinSession::query()->updateOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'arrival_status' => 'arrived',
                    'current_step' => 'identity',
                    'started_by_user_id' => $actorUserId,
                    'started_at' => now(),
                ],
            );

            RoomAvailabilityLock::query()->create([
                'property_id' => $reservation->property_id,
                'room_id' => $room->id,
                'reservation_id' => $reservation->id,
                'locked_by_user_id' => $actorUserId,
                'lock_source' => 'front_desk_assignment',
                'expires_at' => $reservation->check_in_date?->copy()->endOfDay() ?? now()->addHours(4),
            ]);

            $this->statusRecorder->recordReservationTransition(
                $reservation,
                $previousReservationStatus,
                $checkedInFlowStatus,
                $actorUserId,
                $payload['notes'] ?? 'Room assigned to arrival.',
                Room::class,
                $room->id,
            );

            $this->statusRecorder->recordRoomTransition(
                $room,
                'occupancy',
                $roomPreviousStatus?->value ?? $roomPreviousStatus,
                OccupancyStatus::Reserved->value,
                $actorUserId,
                $payload['notes'] ?? 'Room assigned to reservation.',
                Reservation::class,
                $reservation->id,
            );

            FrontDeskAuditLog::query()->create([
                'reservation_id' => $reservation->id,
                'action_type' => 'room_assigned',
                'action_label' => 'Room assigned to arrival',
                'actor_user_id' => $actorUserId,
                'payload_json' => [
                    'room_id' => $room->id,
                    'room_number' => $room->room_number,
                    'notes' => $payload['notes'] ?? null,
                ],
                'happened_at' => now(),
            ]);
        });

        return $reservation->fresh(['assignedRoom', 'primaryGuest', 'roomType', 'property']);
    }
}
