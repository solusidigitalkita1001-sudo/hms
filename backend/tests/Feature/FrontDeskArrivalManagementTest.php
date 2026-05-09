<?php

namespace Tests\Feature;

use App\Domain\Guest\Models\Guest;
use App\Domain\Property\Models\Property;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\StayRecord;
use App\Domain\Room\Models\Room;
use App\Domain\Room\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontDeskArrivalManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_arrival_queue(): void
    {
        $this->seed();

        $token = $this->loginToken();
        $property = Property::query()->where('code', 'MAIN')->firstOrFail();
        $roomType = RoomType::query()->where('property_id', $property->id)->where('code', 'SUP')->firstOrFail();

        $guest = Guest::query()->create([
            'property_id' => $property->id,
            'full_name' => 'Alya Putri',
            'phone' => '081299991111',
            'email' => 'alya@example.com',
        ]);

        Reservation::query()->create([
            'property_id' => $property->id,
            'primary_guest_id' => $guest->id,
            'room_type_id' => $roomType->id,
            'booking_code' => 'ARR-0001',
            'reservation_status' => 'arrival_due',
            'adult_count' => 2,
            'child_count' => 0,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/front-desk/arrivals?search=Alya&per_page=10')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.items.0.booking_code', 'ARR-0001')
            ->assertJsonPath('data.items.0.primary_guest.full_name', 'Alya Putri');
    }

    public function test_authenticated_user_can_assign_room_to_arrival(): void
    {
        $this->seed();

        $token = $this->loginToken();
        $property = Property::query()->where('code', 'MAIN')->firstOrFail();
        $roomType = RoomType::query()->where('property_id', $property->id)->where('code', 'SUP')->firstOrFail();
        $room = Room::query()->where('property_id', $property->id)->where('room_number', '101')->firstOrFail();

        $reservation = Reservation::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'booking_code' => 'ARR-0002',
            'reservation_status' => 'arrival_due',
            'adult_count' => 1,
            'child_count' => 0,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/front-desk/arrivals/{$reservation->id}/assign-room", [
                'room_id' => $room->id,
                'notes' => 'Near lift',
            ])
            ->assertOk()
            ->assertJsonPath('data.assigned_room.room_number', '101')
            ->assertJsonPath('data.reservation_status', 'arrived');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'assigned_room_id' => $room->id,
            'reservation_status' => 'arrived',
        ]);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'current_status' => 'reserved',
        ]);

        $this->assertDatabaseHas('reservation_status_logs', [
            'reservation_id' => $reservation->id,
            'from_status' => 'arrival_due',
            'to_status' => 'arrived',
        ]);

        $this->assertDatabaseHas('room_status_logs', [
            'room_id' => $room->id,
            'status_domain' => 'occupancy',
            'to_status' => 'reserved',
        ]);

        $this->assertDatabaseHas('room_availability_locks', [
            'reservation_id' => $reservation->id,
            'room_id' => $room->id,
            'lock_source' => 'front_desk_assignment',
            'released_at' => null,
        ]);
    }

    public function test_authenticated_user_can_load_assignable_rooms_for_arrival(): void
    {
        $this->seed();

        $token = $this->loginToken();
        $property = Property::query()->where('code', 'MAIN')->firstOrFail();
        $roomType = RoomType::query()->where('property_id', $property->id)->where('code', 'SUP')->firstOrFail();

        $reservation = Reservation::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'booking_code' => 'ARR-ROOMS-01',
            'reservation_status' => 'arrival_due',
            'adult_count' => 1,
            'child_count' => 0,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/front-desk/arrivals/{$reservation->id}/assignable-rooms")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonFragment([
                'room_number' => '101',
                'code' => 'SUP',
            ]);
    }

    public function test_authenticated_user_can_verify_arrival_identity(): void
    {
        $this->seed();

        $token = $this->loginToken();
        $property = Property::query()->where('code', 'MAIN')->firstOrFail();
        $roomType = RoomType::query()->where('property_id', $property->id)->where('code', 'SUP')->firstOrFail();
        $room = Room::query()->where('property_id', $property->id)->where('room_number', '101')->firstOrFail();

        $reservation = Reservation::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'assigned_room_id' => $room->id,
            'booking_code' => 'ARR-0003',
            'reservation_status' => 'arrived',
            'adult_count' => 2,
            'child_count' => 0,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/front-desk/arrivals/{$reservation->id}/verify-identity", [
                'guest' => [
                    'full_name' => 'Rizal Hadi',
                    'id_type' => 'ktp',
                    'id_number' => '3174000000000002',
                    'phone' => '081288887777',
                    'email' => 'rizal@example.com',
                    'nationality' => 'ID',
                    'address' => 'Jakarta',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.primary_guest.full_name', 'Rizal Hadi')
            ->assertJsonPath('data.primary_guest.identity_verified', true)
            ->assertJsonPath('data.reservation_status', 'registration_pending');

        $this->assertDatabaseHas('guests', [
            'full_name' => 'Rizal Hadi',
            'id_number' => '3174000000000002',
            'identity_verification_status' => 'verified',
        ]);

        $this->assertDatabaseHas('reservation_status_logs', [
            'reservation_id' => $reservation->id,
            'from_status' => 'arrived',
            'to_status' => 'registration_pending',
        ]);
    }

    public function test_complete_checkin_requires_assigned_room_and_verified_identity(): void
    {
        $this->seed();

        $token = $this->loginToken();
        $property = Property::query()->where('code', 'MAIN')->firstOrFail();
        $roomType = RoomType::query()->where('property_id', $property->id)->where('code', 'SUP')->firstOrFail();

        $reservation = Reservation::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'booking_code' => 'ARR-0004',
            'reservation_status' => 'arrived',
            'adult_count' => 1,
            'child_count' => 0,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/front-desk/arrivals/{$reservation->id}/complete-check-in", [
                'confirm_identity_verified' => true,
                'confirm_terms_accepted' => true,
                'confirm_registration_signed' => false,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reservation']);
    }

    public function test_authenticated_user_can_complete_checkin(): void
    {
        $this->seed();

        $token = $this->loginToken();
        $property = Property::query()->where('code', 'MAIN')->firstOrFail();
        $roomType = RoomType::query()->where('property_id', $property->id)->where('code', 'SUP')->firstOrFail();
        $room = Room::query()->where('property_id', $property->id)->where('room_number', '101')->firstOrFail();

        $guest = Guest::query()->create([
            'property_id' => $property->id,
            'full_name' => 'Nadia Pramesti',
            'id_type' => 'ktp',
            'id_number' => '3174000000000003',
            'identity_verified' => true,
            'identity_verified_at' => now(),
            'identity_verification_status' => 'verified',
        ]);

        $reservation = Reservation::query()->create([
            'property_id' => $property->id,
            'primary_guest_id' => $guest->id,
            'room_type_id' => $roomType->id,
            'assigned_room_id' => $room->id,
            'booking_code' => 'ARR-0005',
            'reservation_status' => 'registration_pending',
            'adult_count' => 2,
            'child_count' => 0,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/front-desk/arrivals/{$reservation->id}/complete-check-in", [
                'confirm_identity_verified' => true,
                'confirm_terms_accepted' => true,
                'confirm_registration_signed' => true,
                'issue_keycard' => true,
                'notes' => 'Late arrival handled',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_house')
            ->assertJsonPath('data.room_number', '101');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'reservation_status' => 'checked_in',
        ]);

        $this->assertDatabaseHas('stay_records', [
            'reservation_id' => $reservation->id,
            'registration_signed' => true,
        ]);

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'current_status' => 'occupied',
        ]);

        $this->assertDatabaseHas('stay_guests', [
            'stay_record_id' => StayRecord::query()->where('reservation_id', $reservation->id)->firstOrFail()->id,
            'guest_id' => $guest->id,
            'is_primary' => true,
            'occupancy_role' => 'primary',
        ]);

        $this->assertDatabaseHas('reservation_status_logs', [
            'reservation_id' => $reservation->id,
            'from_status' => 'registration_pending',
            'to_status' => 'checked_in',
        ]);

        $this->assertDatabaseHas('room_status_logs', [
            'room_id' => $room->id,
            'status_domain' => 'occupancy',
            'to_status' => 'occupied',
        ]);

        $this->assertDatabaseMissing('room_availability_locks', [
            'reservation_id' => $reservation->id,
            'room_id' => $room->id,
            'released_at' => null,
        ]);
    }

    private function loginToken(): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'identifier' => 'admin',
            'password' => 'password',
        ])->json('data.access_token');
    }
}
