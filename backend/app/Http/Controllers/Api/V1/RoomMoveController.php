<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\FrontDesk\Services\FrontDeskStatusRecorder;
use App\Domain\FrontDesk\Models\FrontDeskAuditLog;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\OccupancyStatus;
use App\Domain\Room\Models\Room;
use App\Domain\Room\Models\RoomAvailabilityLock;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomMoveController extends Controller
{
    public function __construct(
        private readonly FrontDeskStatusRecorder $statusRecorder,
    ) {}

    /**
     * Move a reservation from current room to a new room.
     */
    public function move(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'new_room_id' => ['required', 'exists:rooms,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($reservation->reservation_status !== 'checked_in') {
            throw ValidationException::withMessages([
                'reservation' => 'Hanya reservasi dengan status checked_in yang bisa pindah kamar.',
            ]);
        }

        if (! $reservation->assigned_room_id) {
            throw ValidationException::withMessages([
                'reservation' => 'Reservasi belum memiliki room assignment.',
            ]);
        }

        $newRoom = Room::query()->lockForUpdate()->findOrFail($validated['new_room_id']);

        if ($newRoom->id === $reservation->assigned_room_id) {
            throw ValidationException::withMessages([
                'new_room_id' => 'Kamar baru sama dengan kamar yang sudah ditempati.',
            ]);
        }

        if (! $newRoom->is_active || $newRoom->serviceability_status->value !== 'normal') {
            throw ValidationException::withMessages([
                'new_room_id' => 'Kamar baru tidak tersedia.',
            ]);
        }

        if ($newRoom->current_status->value !== OccupancyStatus::Available->value) {
            throw ValidationException::withMessages([
                'new_room_id' => 'Kamar baru sedang tidak available.',
            ]);
        }

        if (! in_array($newRoom->housekeeping_status->value, [HousekeepingStatus::Clean->value, HousekeepingStatus::Inspected->value], true)) {
            throw ValidationException::withMessages([
                'new_room_id' => 'Kamar baru belum siap (housekeeping).',
            ]);
        }

        DB::transaction(function () use ($reservation, $newRoom, $validated): void {
            $oldRoom = $reservation->assignedRoom;
            $oldRoomId = $oldRoom->id;

            // Release old room → available + dirty (needs cleaning)
            $oldRoom->forceFill([
                'current_status' => OccupancyStatus::Available,
                'housekeeping_status' => HousekeepingStatus::Dirty,
            ])->save();

            $this->statusRecorder->recordRoomTransition(
                $oldRoom,
                'occupancy',
                OccupancyStatus::Occupied->value,
                OccupancyStatus::Available->value,
                auth()->id(),
                'Room released due to move.',
                Reservation::class,
                $reservation->id,
            );

            // Reserve new room
            $newRoom->forceFill([
                'current_status' => OccupancyStatus::Occupied,
            ])->save();

            $this->statusRecorder->recordRoomTransition(
                $newRoom,
                'occupancy',
                OccupancyStatus::Available->value,
                OccupancyStatus::Occupied->value,
                auth()->id(),
                'Room occupied after move.',
                Reservation::class,
                $reservation->id,
            );

            // Update reservation
            $reservation->forceFill([
                'assigned_room_id' => $newRoom->id,
            ])->save();

            // Update stay record
            $stayRecord = $reservation->stayRecords()->where('stay_status', 'in_house')->first();
            if ($stayRecord) {
                $stayRecord->update(['room_id' => $newRoom->id]);
            }

            // Update availability locks
            RoomAvailabilityLock::query()
                ->where('reservation_id', $reservation->id)
                ->whereNull('released_at')
                ->update([
                    'room_id' => $newRoom->id,
                    'updated_at' => now(),
                ]);

            // Record audit log
            FrontDeskAuditLog::query()->create([
                'reservation_id' => $reservation->id,
                'stay_record_id' => $stayRecord?->id,
                'action_type' => 'room_moved',
                'action_label' => 'Room moved',
                'actor_user_id' => auth()->id(),
                'payload_json' => [
                    'from_room_id' => $oldRoomId,
                    'from_room_number' => $oldRoom->room_number,
                    'to_room_id' => $newRoom->id,
                    'to_room_number' => $newRoom->room_number,
                    'reason' => $validated['reason'] ?? null,
                ],
                'happened_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Tamu berhasil dipindahkan ke kamar {$newRoom->room_number}.",
            'data' => [
                'reservation_id' => $reservation->id,
                'old_room_id' => $oldRoomId,
                'new_room_id' => $newRoom->id,
                'new_room_number' => $newRoom->room_number,
            ],
        ]);
    }
}
