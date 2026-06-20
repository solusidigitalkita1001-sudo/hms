<?php

namespace App\Application\FrontDesk\Actions;

use App\Application\FrontDesk\Services\FrontDeskStatusRecorder;
use App\Application\FrontDesk\Services\StayGuestSyncService;
use App\Application\Settings\Services\BusinessDateService;
use App\Domain\FrontDesk\Models\FrontDeskAuditLog;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\ReservationCheckinSession;
use App\Domain\Reservation\Models\StayRecord;
use App\Domain\Room\Enums\OccupancyStatus;
use App\Domain\Room\Models\RoomAvailabilityLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteCheckinAction
{
    public function __construct(
        private readonly FrontDeskStatusRecorder $statusRecorder,
        private readonly StayGuestSyncService $stayGuestSyncService,
        private readonly BusinessDateService $businessDateService,
    ) {}

    public function handle(Reservation $reservation, array $payload, int $actorUserId): StayRecord
    {
        $reservation->loadMissing(['primaryGuest', 'assignedRoom', 'property', 'reservationGuests.guest']);

        if (! $reservation->assigned_room_id) {
            throw ValidationException::withMessages([
                'reservation' => 'Reservasi belum memiliki room assignment.',
            ]);
        }

        if (! $reservation->primaryGuest?->identity_verified) {
            throw ValidationException::withMessages([
                'primary_guest' => 'Identitas tamu utama belum diverifikasi.',
            ]);
        }

        if (! ($payload['confirm_identity_verified'] ?? false)) {
            throw ValidationException::withMessages([
                'confirm_identity_verified' => 'Konfirmasi verifikasi identitas wajib disetujui.',
            ]);
        }

        if (! ($payload['confirm_terms_accepted'] ?? false)) {
            throw ValidationException::withMessages([
                'confirm_terms_accepted' => 'Konfirmasi terms and conditions wajib disetujui.',
            ]);
        }

        $checkinSession = ReservationCheckinSession::query()->firstOrNew([
            'reservation_id' => $reservation->id,
        ]);

        if ($checkinSession->signature_status === 'signed' && ! ($payload['confirm_registration_signed'] ?? false)) {
            throw ValidationException::withMessages([
                'confirm_registration_signed' => 'Konfirmasi signature wajib disetujui.',
            ]);
        }

        return DB::transaction(function () use ($reservation, $payload, $actorUserId, $checkinSession): StayRecord {
            $checkedInAt = now();
            $businessDate = $this->businessDateService->currentBusinessDate($reservation->property);
            $previousReservationStatus = $reservation->reservation_status;
            $room = $reservation->assignedRoom()->lockForUpdate()->firstOrFail();
            $previousRoomStatus = $room->current_status;

            $stayRecord = StayRecord::query()->updateOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'property_id' => $reservation->property_id,
                    'room_id' => $reservation->assigned_room_id,
                    'primary_guest_id' => $reservation->primary_guest_id,
                    'stay_status' => 'in_house',
                    'check_in_business_date' => $businessDate->toDateString(),
                    'actual_check_in_at' => $checkedInAt,
                    'expected_check_out_at' => $reservation->check_out_date?->toDateString()
                        ? $reservation->check_out_date->setTime(12, 0)
                        : null,
                    'checked_in_by_user_id' => $actorUserId,
                    'primary_guest_name_snapshot' => $reservation->primaryGuest->full_name,
                    'registration_signed' => (bool) ($payload['confirm_registration_signed'] ?? false),
                    'registration_signed_at' => ($payload['confirm_registration_signed'] ?? false) ? $checkedInAt : null,
                    'notes' => $payload['notes'] ?? null,
                ],
            );

            $this->stayGuestSyncService->syncFromReservation($stayRecord, $reservation);

            $reservation->forceFill([
                'reservation_status' => 'checked_in',
                'checked_in_at' => $checkedInAt,
                'arrived_at' => $reservation->arrived_at ?? $checkedInAt,
            ])->save();

            $room->forceFill([
                'current_status' => OccupancyStatus::Occupied,
            ])->save();

            $checkinSession->fill([
                'arrival_status' => 'checked_in',
                'current_step' => 'completed',
                'id_verification_status' => 'verified',
                'registration_status' => 'completed',
                'signature_status' => ($payload['confirm_registration_signed'] ?? false)
                    ? 'signed'
                    : ($checkinSession->signature_status ?: 'not_requested'),
                'deposit_status' => $checkinSession->deposit_status ?: 'pending',
                'started_by_user_id' => $checkinSession->started_by_user_id ?: $actorUserId,
                'started_at' => $checkinSession->started_at ?: $checkedInAt,
                'completed_by_user_id' => $actorUserId,
                'completed_at' => $checkedInAt,
            ]);
            $checkinSession->reservation_id = $reservation->id;
            $checkinSession->save();

            RoomAvailabilityLock::query()
                ->where('reservation_id', $reservation->id)
                ->whereNull('released_at')
                ->update([
                    'released_at' => $checkedInAt,
                    'release_reason' => 'checked_in_completed',
                    'updated_at' => $checkedInAt,
                ]);

            $this->statusRecorder->recordReservationTransition(
                $reservation,
                $previousReservationStatus,
                'checked_in',
                $actorUserId,
                $payload['notes'] ?? 'Check-in completed.',
                StayRecord::class,
                $stayRecord->id,
            );

            $this->statusRecorder->recordRoomTransition(
                $room,
                'occupancy',
                $previousRoomStatus?->value ?? $previousRoomStatus,
                OccupancyStatus::Occupied->value,
                $actorUserId,
                'Room occupied after check-in completed.',
                StayRecord::class,
                $stayRecord->id,
            );

            FrontDeskAuditLog::query()->create([
                'reservation_id' => $reservation->id,
                'stay_record_id' => $stayRecord->id,
                'action_type' => 'checkin_completed',
                'action_label' => 'Check-in completed',
                'actor_user_id' => $actorUserId,
                'payload_json' => [
                    'issue_keycard' => (bool) ($payload['issue_keycard'] ?? false),
                    'room_id' => $reservation->assigned_room_id,
                    'room_number' => $reservation->assignedRoom?->room_number,
                ],
                'happened_at' => $checkedInAt,
            ]);

            return $stayRecord->fresh(['reservation', 'room', 'primaryGuest', 'stayGuests']);
        });
    }
}
