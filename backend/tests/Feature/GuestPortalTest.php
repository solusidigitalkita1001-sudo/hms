<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_portal_returns_property_facilities_available_rooms_and_recommendations(): void
    {
        $this->seed();

        $response = $this->getJson('/api/v1/portal/main');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.property.code', 'MAIN')
            ->assertJsonPath('data.branding.app_name', 'Booking WPA Hotel')
            ->assertJsonPath('data.cms.hero_search_button_label', 'Lihat pilihan kamar')
            ->assertJsonCount(4, 'data.facilities')
            ->assertJsonCount(3, 'data.available_room_types')
            ->assertJsonFragment([
                'code' => 'FAM',
                'available_rooms' => 1,
            ])
            ->assertJsonPath('data.summary.available_rooms', 4);
    }
}
