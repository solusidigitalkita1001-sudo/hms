<?php

namespace App\Application\FrontDesk\Actions;

use App\Application\FrontDesk\Services\FrontDeskStatusRecorder;
use App\Domain\FrontDesk\Models\FrontDeskAuditLog;
use App\Domain\Guest\Models\Guest;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\ReservationCheckinSession;
use App\Domain\Reservation\Models\ReservationGuest;
use Illuminate\Support\Facades\DB;

class VerifyArrivalIdentityAction
{
    public function __construct(
        private readonly FrontDeskStatusRecorder $statusRecorder,
    ) {}

    public function handle(Reservation $reservation, array $payload, int $actorUserId): Reservation
    {
        DB::transaction(function () use ($reservation, $payload, $actorUserId): void {
            $guestPayload = $payload['guest'];
            $previousReservationStatus = $reservation->reservation_status;

            $guest = $reservation->primaryGuest ?? new Guest([
                'property_id' => $reservation->property_id,
            ]);

            $guest->fill([
                'property_id' => $reservation->property_id,
                'full_name' => $guestPayload['full_name'],
                'full_name_on_id' => $guestPayload['full_name'],
                'id_type' => $guestPayload['id_type'],
                'id_number' => $guestPayload['id_number'],
                'phone' => $guestPayload['phone'] ?? $guest->phone,
                'email' => $guestPayload['email'] ?? $guest->email,
                'address' => $guestPayload['address'] ?? null,
                'nationality' => $guestPayload['nationality'] ?? null,
                'birth_date' => $guestPayload['birth_date'] ?? null,
                'gender' => $guestPayload['gender'] ?? null,
                'identity_verified' => true,
                'identity_verified_at' => now(),
                'identity_verified_by_user_id' => $actorUserId,
                'identity_verification_status' => 'verified',
                'emergency_contact_name' => $guestPayload['emergency_contact_name'] ?? $guest->emergency_contact_name,
                'emergency_contact_phone' => $guestPayload['emergency_contact_phone'] ?? $guest->emergency_contact_phone,
            ]);
            $guest->save();

            $reservation->forceFill([
                'primary_guest_id' => $guest->id,
                'reservation_status' => $reservation->assigned_room_id ? 'registration_pending' : 'id_pending',
                'arrived_at' => $reservation->arrived_at ?? now(),
            ])->save();

            $this->statusRecorder->recordReservationTransition(
                $reservation,
                $previousReservationStatus,
                $reservation->reservation_status,
                $actorUserId,
                'Primary guest identity verified.',
                Guest::class,
                $guest->id,
            );

            ReservationGuest::query()->updateOrCreate(
                [
                    'reservation_id' => $reservation->id,
                    'is_primary' => true,
                ],
                [
                    'guest_id' => $guest->id,
                    'full_name' => $guest->full_name,
                    'guest_role' => 'primary',
                    'is_registered' => true,
                    'id_type' => $guest->id_type,
                    'id_number' => $guest->id_number,
                ],
            );

            ReservationCheckinSession::query()->updateOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'arrival_status' => 'arrived',
                    'current_step' => $reservation->assigned_room_id ? 'review' : 'room_and_billing',
                    'id_verification_status' => 'verified',
                    'registration_status' => 'in_progress',
                    'started_by_user_id' => $actorUserId,
                    'started_at' => now(),
                ],
            );

            FrontDeskAuditLog::query()->create([
                'reservation_id' => $reservation->id,
                'action_type' => 'identity_verified',
                'action_label' => 'Primary guest identity verified',
                'actor_user_id' => $actorUserId,
                'payload_json' => [
                    'guest_id' => $guest->id,
                    'full_name' => $guest->full_name,
                    'id_type' => $guest->id_type,
                    'id_number' => $guest->id_number,
                ],
                'happened_at' => now(),
            ]);
        });

        return $reservation->fresh(['primaryGuest', 'assignedRoom', 'roomType', 'property']);
    }
}
