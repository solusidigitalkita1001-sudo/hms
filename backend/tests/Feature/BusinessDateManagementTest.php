<?php

namespace Tests\Feature;

use App\Domain\Guest\Models\Guest;
use App\Domain\Property\Models\NightAudit;
use App\Domain\Property\Models\Property;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\StayRecord;
use App\Domain\Room\Models\Room;
use App\Domain\Room\Models\RoomType;
use App\Domain\Setting\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessDateManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_business_date_snapshot(): void
    {
        $this->seed();

        $token = $this->loginToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/settings/business-date?property_code=MAIN')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.property.code', 'MAIN')
            ->assertJsonPath('data.night_audit_cutoff_time', '02:00');
    }

    public function test_authenticated_user_can_run_night_audit_and_advance_business_date(): void
    {
        $this->seed();

        $token = $this->loginToken();
        $property = Property::query()->where('code', 'MAIN')->firstOrFail();

        Setting::query()->updateOrCreate(
            [
                'property_id' => $property->id,
                'setting_group' => 'business',
                'setting_key' => 'current_business_date',
            ],
            [
                'setting_value' => '2026-05-24',
            ],
        );

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/settings/night-audit', [
                'property_code' => 'MAIN',
                'notes' => 'Closing shift and moving to next business date.',
            ])
            ->assertOk()
            ->assertJsonPath('data.audit.business_date', '2026-05-24')
            ->assertJsonPath('data.audit.next_business_date', '2026-05-25')
            ->assertJsonPath('data.business_date.current_business_date', '2026-05-25');

        $nightAudit = NightAudit::query()
            ->where('property_id', $property->id)
            ->firstOrFail();

        $this->assertSame('2026-05-24', $nightAudit->business_date?->toDateString());
        $this->assertSame('2026-05-25', $nightAudit->next_business_date?->toDateString());
        $this->assertSame('completed', $nightAudit->status);

        $this->assertDatabaseHas('settings', [
            'property_id' => $property->id,
            'setting_group' => 'business',
            'setting_key' => 'current_business_date',
            'setting_value' => '2026-05-25',
        ]);
    }

    public function test_complete_checkin_uses_current_business_date_setting(): void
    {
        $this->seed();

        $token = $this->loginToken();
        $property = Property::query()->where('code', 'MAIN')->firstOrFail();
        $roomType = RoomType::query()->where('property_id', $property->id)->where('code', 'SUP')->firstOrFail();
        $room = Room::query()->where('property_id', $property->id)->where('room_number', '101')->firstOrFail();

        Setting::query()->updateOrCreate(
            [
                'property_id' => $property->id,
                'setting_group' => 'business',
                'setting_key' => 'current_business_date',
            ],
            [
                'setting_value' => '2026-05-24',
            ],
        );

        $guest = Guest::query()->create([
            'property_id' => $property->id,
            'full_name' => 'Business Date Guest',
            'id_type' => 'ktp',
            'id_number' => '3174000000000099',
            'identity_verified' => true,
            'identity_verified_at' => now(),
            'identity_verification_status' => 'verified',
        ]);

        $reservation = Reservation::query()->create([
            'property_id' => $property->id,
            'primary_guest_id' => $guest->id,
            'room_type_id' => $roomType->id,
            'assigned_room_id' => $room->id,
            'booking_code' => 'ARR-BIZDATE-01',
            'reservation_status' => 'registration_pending',
            'adult_count' => 1,
            'child_count' => 0,
            'check_in_date' => '2026-05-24',
            'check_out_date' => '2026-05-25',
        ]);

        $this->travelTo(now()->setDate(2026, 5, 25)->setTime(0, 30));

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/front-desk/arrivals/{$reservation->id}/complete-check-in", [
                'confirm_identity_verified' => true,
                'confirm_terms_accepted' => true,
                'confirm_registration_signed' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_house');

        $stayRecord = StayRecord::query()
            ->where('reservation_id', $reservation->id)
            ->firstOrFail();

        $this->assertSame('2026-05-24', $stayRecord->check_in_business_date?->toDateString());
    }

    private function loginToken(): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'identifier' => 'admin',
            'password' => 'password',
        ])->json('data.access_token');
    }
}
