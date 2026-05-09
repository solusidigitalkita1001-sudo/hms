<?php

namespace Tests\Feature;

use App\Domain\Reservation\Models\ReservationInquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_portal_inquiry(): void
    {
        $this->seed();

        $response = $this->postJson('/api/v1/portal/main/inquiries', [
            'room_type_code' => 'DLX',
            'full_name' => 'Alya Putri',
            'phone' => '081299991111',
            'email' => 'alya@example.com',
            'check_in_date' => now()->addDays(3)->format('Y-m-d'),
            'check_out_date' => now()->addDays(5)->format('Y-m-d'),
            'guest_count' => 2,
            'notes' => 'Butuh kamar non-smoking.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.room_type_code', 'DLX');

        $this->assertDatabaseHas('reservation_inquiries', [
            'full_name' => 'Alya Putri',
            'phone' => '081299991111',
            'status' => 'new',
            'source' => 'portal',
        ]);
    }

    public function test_portal_inquiry_requires_valid_room_type_for_property(): void
    {
        $this->seed();

        $response = $this->postJson('/api/v1/portal/main/inquiries', [
            'room_type_code' => 'XXX',
            'full_name' => 'Alya Putri',
            'phone' => '081299991111',
            'check_in_date' => now()->addDays(3)->format('Y-m-d'),
            'check_out_date' => now()->addDays(5)->format('Y-m-d'),
            'guest_count' => 2,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['room_type_code']);

        $this->assertSame(0, ReservationInquiry::query()->count());
    }
}
