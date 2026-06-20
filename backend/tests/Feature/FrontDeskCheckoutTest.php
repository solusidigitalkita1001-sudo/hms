<?php

namespace Tests\Feature;

use App\Domain\Auth\Models\ApiToken;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceItem;
use App\Domain\Guest\Models\Guest;
use App\Domain\Property\Models\Property;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\StayRecord;
use App\Domain\Room\Models\Room;
use App\Domain\Room\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FrontDeskCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Property $property;
    private RoomType $roomType;
    private Room $room;
    private Guest $guest;
    private Reservation $reservation;
    private StayRecord $stayRecord;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user & API token
        $this->user = User::factory()->create();
        $plainTextToken = Str::random(40);
        $token = ApiToken::query()->create([
            'user_id' => $this->user->id,
            'name' => 'test-token',
            'token' => hash('sha256', $plainTextToken),
        ]);
        $this->withHeader('Authorization', 'Bearer '.$token->id.'|'.$plainTextToken);

        // Create test data
        $this->property = Property::factory()->create(['code' => 'TEST']);

        $this->roomType = RoomType::factory()->create([
            'property_id' => $this->property->id,
            'base_price' => 500000,
        ]);

        $this->room = Room::factory()->create([
            'property_id' => $this->property->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => '101',
            'current_status' => 'occupied',
        ]);

        $this->guest = Guest::factory()->create(['identity_verified' => true]);

        $this->reservation = Reservation::factory()->create([
            'property_id' => $this->property->id,
            'primary_guest_id' => $this->guest->id,
            'room_type_id' => $this->roomType->id,
            'assigned_room_id' => $this->room->id,
            'reservation_status' => 'checked_in',
            'check_in_date' => now()->subDays(2),
            'check_out_date' => now(),
            'checked_in_at' => now()->subDays(2),
            'deposit_amount' => 500000,
        ]);

        $this->stayRecord = StayRecord::factory()->create([
            'reservation_id' => $this->reservation->id,
            'property_id' => $this->property->id,
            'room_id' => $this->room->id,
            'primary_guest_id' => $this->guest->id,
            'stay_status' => 'in_house',
            'actual_check_in_at' => now()->subDays(2),
            'expected_check_out_at' => now()->setTime(12, 0),
        ]);
    }

    public function test_checkout_preview_calculates_correct_bill(): void
    {
        // Create existing invoice with some items
        $invoice = Invoice::factory()->create([
            'reservation_id' => $this->reservation->id,
            'invoice_number' => 'TEST-001',
            'invoice_status' => 'draft',
            'subtotal_amount' => 0,
            'grand_total' => 0,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'item_type' => 'amenity',
            'item_name' => 'Breakfast',
            'unit_price' => 100000,
            'quantity' => 2,
            'tax_amount' => 20000,
            'line_total' => 220000,
        ]);

        // Act
        $response = $this->getJson("/api/v1/front-desk/departures/{$this->reservation->id}/preview");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'reservation',
                    'room',
                    'invoice' => [
                        'items',
                        'subtotal_amount',
                        'tax_amount',
                        'grand_total',
                    ],
                    'payments',
                ],
            ]);
    }

    public function test_checkout_updates_reservation_and_room_status(): void
    {
        // Create invoice
        $invoice = Invoice::factory()->create([
            'reservation_id' => $this->reservation->id,
            'invoice_number' => 'TEST-002',
            'invoice_status' => 'unpaid',
            'grand_total' => 1000000,
            'paid_amount' => 500000,
        ]);

        // Act
        $response = $this->postJson("/api/v1/front-desk/departures/{$this->reservation->id}/complete-checkout", [
            'room_inspected' => true,
            'keycard_returned' => true,
            'payment_method_code' => 'cash',
            'payment_amount' => 500000,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'reservation_status' => 'checked_out',
                    'room_status' => 'available',
                ],
            ]);

        // Check database
        $this->assertDatabaseHas('reservations', [
            'id' => $this->reservation->id,
            'reservation_status' => 'checked_out',
        ]);

        $this->assertDatabaseHas('rooms', [
            'id' => $this->room->id,
            'current_status' => 'available',
            'housekeeping_status' => 'dirty',
        ]);

        $this->assertDatabaseHas('stay_records', [
            'id' => $this->stayRecord->id,
            'stay_status' => 'checked_out',
        ]);

        $this->assertDatabaseHas('housekeeping_tasks', [
            'reservation_id' => $this->reservation->id,
            'room_id' => $this->room->id,
            'task_type' => 'checkout_cleaning',
            'task_status' => 'pending',
        ]);
    }

    public function test_checkout_with_damage_fee(): void
    {
        $invoice = Invoice::factory()->create([
            'reservation_id' => $this->reservation->id,
            'invoice_number' => 'TEST-003',
            'invoice_status' => 'unpaid',
            'grand_total' => 1000000,
            'paid_amount' => 500000,
        ]);

        $response = $this->postJson("/api/v1/front-desk/departures/{$this->reservation->id}/complete-checkout", [
            'damage_fee_amount' => 200000,
            'damage_fee_notes' => 'Broken mirror',
            'payment_method_code' => 'cash',
            'payment_amount' => 700000,
        ]);

        $response->assertStatus(200);

        // Check damage fee item added
        $this->assertDatabaseHas('invoice_items', [
            'item_type' => 'damage_fee',
            'item_name' => 'Damage Fee',
        ]);
    }

    public function test_checkout_fails_for_non_checked_in_reservation(): void
    {
        $this->reservation->update(['reservation_status' => 'confirmed']);

        $response = $this->postJson("/api/v1/front-desk/departures/{$this->reservation->id}/complete-checkout");

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Reservasi tidak sedang checked_in.');
    }
}