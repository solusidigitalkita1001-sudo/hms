<?php

namespace Tests\Feature;

use App\Domain\Property\Models\Property;
use App\Domain\Reservation\Models\ReservationInquiry;
use App\Domain\Room\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationInquiryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_reservation_inquiries(): void
    {
        $this->seed();

        $token = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'admin',
            'password' => 'password',
        ])->json('data.access_token');

        $property = Property::query()->where('code', 'MAIN')->firstOrFail();
        $roomType = RoomType::query()->where('property_id', $property->id)->firstOrFail();

        ReservationInquiry::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'full_name' => 'Alya Putri',
            'phone' => '081299991111',
            'email' => 'alya@example.com',
            'check_in_date' => now()->addDays(2)->toDateString(),
            'check_out_date' => now()->addDays(4)->toDateString(),
            'guest_count' => 2,
            'source' => 'portal',
            'status' => ReservationInquiry::STATUS_NEW,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/reservation-inquiries')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('data.items.0.full_name', 'Alya Putri')
            ->assertJsonPath('data.items.0.status', ReservationInquiry::STATUS_NEW);
    }

    public function test_authenticated_user_can_paginate_and_sort_reservation_inquiries(): void
    {
        $this->seed();

        $token = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'admin',
            'password' => 'password',
        ])->json('data.access_token');

        $property = Property::query()->where('code', 'MAIN')->firstOrFail();
        $roomType = RoomType::query()->where('property_id', $property->id)->firstOrFail();

        ReservationInquiry::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'full_name' => 'Zeta Guest',
            'phone' => '081200000001',
            'email' => 'zeta@example.com',
            'check_in_date' => now()->addDays(1)->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'guest_count' => 2,
            'source' => 'portal',
            'status' => ReservationInquiry::STATUS_NEW,
        ]);

        ReservationInquiry::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'full_name' => 'Alpha Guest',
            'phone' => '081200000002',
            'email' => 'alpha@example.com',
            'check_in_date' => now()->addDays(3)->toDateString(),
            'check_out_date' => now()->addDays(5)->toDateString(),
            'guest_count' => 3,
            'source' => 'portal',
            'status' => ReservationInquiry::STATUS_CONTACTED,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/reservation-inquiries?per_page=1&sort_by=full_name&sort_direction=asc')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.last_page', 1)
            ->assertJsonPath('data.sort.by', 'full_name')
            ->assertJsonPath('data.sort.direction', 'asc')
            ->assertJsonPath('data.items.0.full_name', 'Alpha Guest');
    }

    public function test_authenticated_user_can_update_reservation_inquiry_status(): void
    {
        $this->seed();

        $token = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'admin',
            'password' => 'password',
        ])->json('data.access_token');

        $property = Property::query()->where('code', 'MAIN')->firstOrFail();
        $roomType = RoomType::query()->where('property_id', $property->id)->firstOrFail();

        $inquiry = ReservationInquiry::query()->create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'full_name' => 'Rizal Hadi',
            'phone' => '081288887777',
            'email' => null,
            'check_in_date' => now()->addDays(3)->toDateString(),
            'check_out_date' => now()->addDays(5)->toDateString(),
            'guest_count' => 3,
            'source' => 'portal',
            'status' => ReservationInquiry::STATUS_NEW,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/reservation-inquiries/{$inquiry->id}/status", [
                'status' => ReservationInquiry::STATUS_CONTACTED,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', ReservationInquiry::STATUS_CONTACTED);

        $this->assertDatabaseHas('reservation_inquiries', [
            'id' => $inquiry->id,
            'status' => ReservationInquiry::STATUS_CONTACTED,
        ]);
    }
}
