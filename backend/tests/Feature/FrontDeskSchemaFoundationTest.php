<?php

namespace Tests\Feature;

use App\Domain\FrontDesk\Models\FrontDeskAuditLog;
use App\Domain\Guest\Models\Guest;
use App\Domain\Property\Models\Property;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\ReservationCheckinSession;
use App\Domain\Reservation\Models\ReservationGuest;
use App\Domain\Reservation\Models\StayRecord;
use App\Domain\Room\Models\Room;
use App\Domain\Room\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontDeskSchemaFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_desk_schema_foundation_supports_guest_reservation_and_checkin_relations(): void
    {
        $this->seed();

        $property = Property::query()->where('code', 'MAIN')->firstOrFail();
        $roomType = RoomType::query()->where('property_id', $property->id)->where('code', 'SUP')->firstOrFail();
        $room = Room::query()->where('property_id', $property->id)->where('room_number', '101')->firstOrFail();
        $user = User::query()->where('username', 'admin')->firstOrFail();

        $guest = Guest::query()->create([
            'property_id' => $property->id,
            'full_name' => 'Alya Putri',
            'full_name_on_id' => 'Alya Putri',
            'id_type' => 'ktp',
            'id_number' => '3174000000000001',
            'phone' => '081299991111',
            'email' => 'alya@example.com',
            'nationality' => 'ID',
            'identity_verified' => true,
            'identity_verified_at' => now(),
            'identity_verified_by_user_id' => $user->id,
            'identity_verification_status' => 'verified',
            'total_stays' => 1,
        ]);

        $reservation = Reservation::query()->create([
            'property_id' => $property->id,
            'primary_guest_id' => $guest->id,
            'room_type_id' => $roomType->id,
            'assigned_room_id' => $room->id,
            'booking_code' => 'RSV-0001',
            'source' => 'direct',
            'reservation_status' => 'ready_for_checkin',
            'adult_count' => 2,
            'child_count' => 0,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
            'payment_status' => 'pending',
            'guarantee_status' => 'guaranteed',
            'deposit_amount' => 300000,
            'booked_at' => now()->subDay(),
            'arrived_at' => now(),
            'created_by_user_id' => $user->id,
        ]);

        $reservationGuest = ReservationGuest::query()->create([
            'reservation_id' => $reservation->id,
            'guest_id' => $guest->id,
            'full_name' => $guest->full_name,
            'guest_role' => 'primary',
            'is_primary' => true,
            'is_registered' => true,
            'id_type' => 'ktp',
            'id_number' => '3174000000000001',
        ]);

        $checkinSession = ReservationCheckinSession::query()->create([
            'reservation_id' => $reservation->id,
            'arrival_status' => 'arrived',
            'current_step' => 'review',
            'id_verification_status' => 'verified',
            'registration_status' => 'completed',
            'signature_status' => 'not_requested',
            'deposit_status' => 'received',
            'started_by_user_id' => $user->id,
            'started_at' => now(),
        ]);

        $stayRecord = StayRecord::query()->create([
            'reservation_id' => $reservation->id,
            'property_id' => $property->id,
            'room_id' => $room->id,
            'primary_guest_id' => $guest->id,
            'stay_status' => 'in_house',
            'actual_check_in_at' => now(),
            'expected_check_out_at' => now()->addDay(),
            'checked_in_by_user_id' => $user->id,
            'primary_guest_name_snapshot' => $guest->full_name,
            'registration_signed' => true,
            'registration_signed_at' => now(),
        ]);

        $auditLog = FrontDeskAuditLog::query()->create([
            'reservation_id' => $reservation->id,
            'stay_record_id' => $stayRecord->id,
            'action_type' => 'checkin_completed',
            'action_label' => 'Check-in completed',
            'actor_user_id' => $user->id,
            'payload_json' => [
                'booking_code' => $reservation->booking_code,
                'room_number' => $room->room_number,
            ],
            'happened_at' => now(),
        ]);

        $this->assertTrue($guest->identity_verified);
        $this->assertSame('verified', $guest->identity_verification_status);
        $this->assertSame($guest->id, $reservation->primaryGuest->id);
        $this->assertSame($room->id, $reservation->assignedRoom->id);
        $this->assertSame($reservationGuest->id, $reservation->reservationGuests->firstOrFail()->id);
        $this->assertSame($checkinSession->id, $reservation->checkinSessions->firstOrFail()->id);
        $this->assertSame($stayRecord->id, $reservation->stayRecords->firstOrFail()->id);
        $this->assertSame($auditLog->id, $stayRecord->frontDeskAuditLogs->firstOrFail()->id);
        $this->assertSame('RSV-0001', $auditLog->payload_json['booking_code']);
    }
}
