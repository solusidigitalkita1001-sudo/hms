<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\FrontDesk\Services\FrontDeskStatusRecorder;
use App\Domain\FrontDesk\Models\FrontDeskAuditLog;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Room\Enums\OccupancyStatus;
use App\Domain\Room\Models\RoomAvailabilityLock;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NoShowController extends Controller
{
    public function __construct(
        private readonly FrontDeskStatusRecorder $statusRecorder,
    ) {}

    /**
     * Mark a reservation as no-show.
     */
    public function markNoShow(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $allowedStatuses = ['reserved', 'arrival_due', 'confirmed', 'id_pending', 'registration_pending'];

        if (! in_array($reservation->reservation_status, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'reservation' => 'Reservasi dengan status ini tidak bisa di-mark sebagai no-show.',
            ]);
        }

        DB::transaction(function () use ($reservation, $validated): void {
            $previousStatus = $reservation->reservation_status;

            // Update reservation
            $reservation->forceFill([
                'reservation_status' => 'no_show',
                'no_show_at' => now(),
                'status_reason' => $validated['reason'] ?? 'Guest did not arrive.',
            ])->save();

            // Release room assignment if any
            if ($reservation->assignedRoom) {
                $room = $reservation->assignedRoom;
                $room->forceFill([
                    'current_status' => OccupancyStatus::Available,
                ])->save();

                $this->statusRecorder->recordRoomTransition(
                    $room,
                    'occupancy',
                    $room->current_status?->value ?? $previousStatus,
                    OccupancyStatus::Available->value,
                    auth()->id(),
                    'Room released due to no-show.',
                    Reservation::class,
                    $reservation->id,
                );
            }

            // Release availability locks
            RoomAvailabilityLock::query()
                ->where('reservation_id', $reservation->id)
                ->whereNull('released_at')
                ->update([
                    'released_at' => now(),
                    'release_reason' => 'no_show',
                    'updated_at' => now(),
                ]);

            // Void any existing invoices
            $reservation->invoices()
                ->where('invoice_status', 'draft')
                ->update(['invoice_status' => 'void']);

            // Record status transition
            $this->statusRecorder->recordReservationTransition(
                $reservation,
                $previousStatus,
                'no_show',
                auth()->id(),
                $validated['reason'] ?? 'Marked as no-show.',
                null,
                null,
            );

            // Record audit log
            FrontDeskAuditLog::query()->create([
                'reservation_id' => $reservation->id,
                'action_type' => 'no_show',
                'action_label' => 'Marked as No-Show',
                'actor_user_id' => auth()->id(),
                'payload_json' => [
                    'previous_status' => $previousStatus,
                    'had_room_assignment' => $reservation->assigned_room_id !== null,
                    'reason' => $validated['reason'] ?? null,
                ],
                'happened_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Reservasi telah di-mark sebagai no-show.',
            'data' => [
                'reservation_id' => $reservation->id,
                'booking_code' => $reservation->booking_code,
                'status' => 'no_show',
                'no_show_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
