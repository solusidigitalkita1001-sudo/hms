<?php

namespace App\Domain\FrontDesk\Services;

use App\Application\FrontDesk\Services\AvailabilityService;
use App\Application\FrontDesk\Services\FrontDeskStatusRecorder;
use App\Application\Settings\Services\BusinessDateService;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceItem;
use App\Domain\Billing\Models\Payment;
use App\Domain\Guest\Models\Guest;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\ReservationCheckinSession;
use App\Domain\Reservation\Models\StayRecord;
use App\Domain\Room\Enums\OccupancyStatus;
use App\Domain\Room\Models\Room;
use App\Domain\Room\Models\RoomAvailabilityLock;
use App\Domain\Room\Models\RoomType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WalkInService
{
    public function __construct(
        private readonly BusinessDateService $businessDateService,
        private readonly FrontDeskStatusRecorder $statusRecorder,
        private readonly AvailabilityService $availabilityService,
    ) {}

    /**
     * Create walk-in reservation
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createWalkIn(array $payload, User $actor): array
    {
        return DB::transaction(function () use ($payload, $actor) {
            $propertyId = $payload['property_id'] ?? null;
            $checkInDate = \Carbon\Carbon::parse($payload['check_in_date']);
            $checkOutDate = \Carbon\Carbon::parse($payload['check_out_date']);

            // Get or create guest
            $guest = $this->getOrCreateGuest($payload, $propertyId);

            // Get room type
            $roomType = RoomType::findOrFail($payload['room_type_id']);

            // Calculate pricing
            $nights = $checkInDate->diffInDays($checkOutDate);
            $ratePerNight = $payload['rate_per_night'] ?? $roomType->base_price;
            $totalRoomCharge = $ratePerNight * $nights;

            // Assign room (auto or manual)
            $room = null;
            if (! empty($payload['room_id'])) {
                $room = Room::findOrFail($payload['room_id']);
                $this->validateRoomAvailability($room, $checkInDate, $checkOutDate);
            } else {
                $room = $this->autoAssignRoom($roomType, $propertyId, $checkInDate, $checkOutDate);
            }

            // Create reservation
            $reservation = $this->createReservation($payload, $guest, $roomType, $room, $checkInDate, $checkOutDate, $totalRoomCharge, $actor);

            // Lock room availability
            $this->lockRoomAvailability($reservation, $room, $checkInDate, $checkOutDate);

            // Auto check-in if requested
            $stayRecord = null;
            if ($payload['auto_check_in'] ?? false) {
                $stayRecord = $this->performCheckIn($reservation, $room, $guest, $actor);
            }

            // Create invoice if requested
            $invoice = null;
            if ($payload['create_invoice'] ?? true) {
                $invoice = $this->createInvoice($reservation, $room, $nights, $ratePerNight, $actor);
            }

            // Process payment if provided
            $payment = null;
            if (! empty($payload['payment_amount']) && ! empty($payload['payment_method_code'])) {
                $payment = $this->processPayment($invoice, $payload, $actor);
            }

            return $this->formatWalkInResponse($reservation, $guest, $room, $stayRecord, $invoice, $payment);
        });
    }

    /**
     * Get or create guest from payload
     */
    protected function getOrCreateGuest(array $payload, $propertyId): Guest
    {
        if (! empty($payload['guest_id'])) {
            return Guest::findOrFail($payload['guest_id']);
        }

        return Guest::query()->create([
            'property_id' => $propertyId,
            'full_name' => $payload['guest_full_name'],
            'phone' => $payload['guest_phone'],
            'email' => $payload['guest_email'] ?? null,
            'id_type' => $payload['guest_id_type'] ?? null,
            'id_number' => $payload['guest_id_number'] ?? null,
            'identity_verified' => true, // Verified by front desk
            'identity_verified_at' => now(),
            'identity_verification_status' => 'verified',
        ]);
    }

    /**
     * Validate room availability
     */
    protected function validateRoomAvailability(Room $room, $checkInDate, $checkOutDate): void
    {
        $isAvailable = $this->availabilityService->isRoomAvailable(
            $room->id,
            $checkInDate,
            $checkOutDate,
        );

        if (! $isAvailable) {
            throw new \InvalidArgumentException("Room {$room->room_number} is not available for the selected dates.");
        }
    }

    /**
     * Auto assign available room
     */
    protected function autoAssignRoom(RoomType $roomType, $propertyId, $checkInDate, $checkOutDate): Room
    {
        $availableRoom = $this->availabilityService->findAvailableRoom(
            $roomType->id,
            $propertyId,
            $checkInDate,
            $checkOutDate,
        );

        if (! $availableRoom) {
            throw new \InvalidArgumentException('No available rooms found for the selected room type and dates.');
        }

        return $availableRoom;
    }

    /**
     * Create reservation
     */
    protected function createReservation(
        array $payload,
        Guest $guest,
        RoomType $roomType,
        ?Room $room,
        $checkInDate,
        $checkOutDate,
        $totalRoomCharge,
        User $actor,
    ): Reservation {
        $bookingCode = $this->generateBookingCode($roomType->property_id ?? $payload['property_id'] ?? 1);

        $reservation = Reservation::query()->create([
            'property_id' => $roomType->property_id ?? $payload['property_id'],
            'primary_guest_id' => $guest->id,
            'room_type_id' => $roomType->id,
            'assigned_room_id' => $room?->id,
            'booking_code' => $bookingCode,
            'source' => $payload['source'] ?? 'walk_in',
            'reservation_status' => ($payload['auto_check_in'] ?? false) ? 'checked_in' : 'confirmed',
            'adult_count' => $payload['adult_count'] ?? 1,
            'child_count' => $payload['child_count'] ?? 0,
            'check_in_date' => $checkInDate,
            'check_out_date' => $checkOutDate,
            'payment_status' => 'pending',
            'guarantee_status' => 'walk_in_guaranteed',
            'deposit_amount' => $payload['deposit_amount'] ?? 0,
            'special_requests' => $payload['special_requests'] ?? null,
            'internal_notes' => $payload['internal_notes'] ?? null,
            'booked_at' => now(),
            'arrived_at' => ($payload['auto_check_in'] ?? false) ? now() : null,
            'checked_in_at' => ($payload['auto_check_in'] ?? false) ? now() : null,
            'created_by_user_id' => $actor->id,
        ]);

        // Record status log
        $this->statusRecorder->recordReservationTransition(
            $reservation,
            null,
            $reservation->reservation_status,
            $actor->id,
            'Walk-in reservation created',
        );

        return $reservation;
    }

    /**
     * Lock room availability
     */
    protected function lockRoomAvailability(Reservation $reservation, Room $room, $checkInDate, $checkOutDate): void
    {
        if (! $room) {
            return;
        }

        RoomAvailabilityLock::query()->create([
            'property_id' => $reservation->property_id,
            'room_id' => $room->id,
            'reservation_id' => $reservation->id,
            'locked_by_user_id' => $actor->id ?? auth()->id(),
            'lock_source' => 'walk_in',
            'lock_type' => 'reservation',
            'lock_start_date' => $checkInDate->toDateString(),
            'lock_end_date' => $checkOutDate->toDateString(),
            'expires_at' => $checkOutDate->copy()->endOfDay(),
            'notes' => 'Walk-in reservation lock',
        ]);
    }

    /**
     * Perform check-in
     */
    protected function performCheckIn(Reservation $reservation, Room $room, Guest $guest, User $actor): StayRecord
    {
        $businessDate = $this->businessDateService->currentBusinessDate($room->property);

        // Update reservation status
        $reservation->update([
            'reservation_status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        // Update room status to occupied
        $room->update([
            'current_status' => OccupancyStatus::Occupied,
        ]);

        // Create stay record
        $stayRecord = StayRecord::query()->create([
            'reservation_id' => $reservation->id,
            'property_id' => $reservation->property_id,
            'room_id' => $room->id,
            'primary_guest_id' => $guest->id,
            'stay_status' => 'in_house',
            'check_in_business_date' => $businessDate->toDateString(),
            'actual_check_in_at' => now(),
            'expected_check_out_at' => $reservation->check_out_date?->setTime(12, 0),
            'checked_in_by_user_id' => $actor->id,
            'primary_guest_name_snapshot' => $guest->full_name,
        ]);

        return $stayRecord;
    }

    /**
     * Create invoice for walk-in
     */
    protected function createInvoice(Reservation $reservation, $room, $nights, $ratePerNight, User $actor): Invoice
    {
        $businessDate = $this->businessDateService->currentBusinessDate($room->property);
        $totalRoomCharge = $ratePerNight * $nights;
        $taxAmount = $totalRoomCharge * 0.1;

        $invoice = Invoice::query()->create([
            'reservation_id' => $reservation->id,
            'invoice_number' => $this->generateInvoiceNumber($room->property, $businessDate),
            'invoice_status' => 'unpaid',
            'issued_at' => now(),
            'subtotal_amount' => $totalRoomCharge,
            'tax_amount' => $taxAmount,
            'service_amount' => 0,
            'discount_amount' => 0,
            'grand_total' => $totalRoomCharge + $taxAmount,
            'paid_amount' => 0,
            'remaining_amount' => $totalRoomCharge + $taxAmount,
            'created_by_user_id' => $actor->id,
        ]);

        // Add room charge item
        $invoice->items()->create([
            'item_type' => 'room_charge',
            'item_name' => "Room Charge ({$nights} nights)",
            'description' => "Room {$room->room_number} - {$reservation->roomType->name}",
            'unit_price' => $ratePerNight,
            'quantity' => $nights,
            'discount_amount' => 0,
            'tax_amount' => $taxAmount,
            'line_total' => $totalRoomCharge + $taxAmount,
            'item_date' => now()->toDateString(),
        ]);

        return $invoice;
    }

    /**
     * Process payment
     */
    protected function processPayment(Invoice $invoice, array $payload, User $actor): Payment
    {
        $businessDate = $this->businessDateService->currentBusinessDate($invoice->reservation->property);

        $payment = Payment::query()->create([
            'invoice_id' => $invoice->id,
            'payment_code' => $this->generatePaymentCode($invoice->reservation->property, $businessDate),
            'payment_type' => 'walk_in_deposit',
            'payment_status' => 'completed',
            'payment_method_code' => $payload['payment_method_code'],
            'amount' => $payload['payment_amount'],
            'payment_reference' => $payload['payment_reference'] ?? null,
            'business_date' => $businessDate->toDateString(),
            'paid_at' => now(),
            'received_by_user_id' => $actor->id,
            'notes' => 'Walk-in deposit/payment',
        ]);

        // Update invoice
        $invoice->update([
            'paid_amount' => $invoice->paid_amount + $payment->amount,
        ]);
        $invoice->recalculateTotals();
        $invoice->save();

        return $payment;
    }

    /**
     * Format walk-in response
     */
    protected function formatWalkInResponse($reservation, $guest, $room, $stayRecord, $invoice, $payment): array
    {
        return [
            'reservation' => [
                'id' => $reservation->id,
                'booking_code' => $reservation->booking_code,
                'reservation_status' => $reservation->reservation_status,
                'source' => $reservation->source,
                'check_in_date' => $reservation->check_in_date?->format('Y-m-d'),
                'check_out_date' => $reservation->check_out_date?->format('Y-m-d'),
                'adult_count' => $reservation->adult_count,
                'child_count' => $reservation->child_count,
                'deposit_amount' => (float) $reservation->deposit_amount,
                'special_requests' => $reservation->special_requests,
            ],
            'guest' => [
                'id' => $guest->id,
                'full_name' => $guest->full_name,
                'phone' => $guest->phone,
                'email' => $guest->email,
                'identity_verified' => $guest->identity_verified,
            ],
            'room' => $room ? [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'room_type' => $reservation->roomType?->name,
                'current_status' => $room->current_status?->value ?? $room->current_status,
            ] : null,
            'stay_record' => $stayRecord ? [
                'id' => $stayRecord->id,
                'stay_status' => $stayRecord->stay_status,
                'actual_check_in_at' => $stayRecord->actual_check_in_at?->format('Y-m-d H:i:s'),
            ] : null,
            'invoice' => $invoice ? [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_status' => $invoice->invoice_status,
                'subtotal_amount' => (float) $invoice->subtotal_amount,
                'tax_amount' => (float) $invoice->tax_amount,
                'grand_total' => (float) $invoice->grand_total,
                'paid_amount' => (float) $invoice->paid_amount,
                'remaining_amount' => (float) $invoice->remaining_amount,
            ] : null,
            'payment' => $payment ? [
                'id' => $payment->id,
                'payment_code' => $payment->payment_code,
                'payment_method_code' => $payment->payment_method_code,
                'amount' => (float) $payment->amount,
                'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
            ] : null,
        ];
    }

    protected function generateBookingCode($propertyId): string
    {
        $prefix = 'WK-'.now()->format('Ymd');
        $count = Reservation::where('booking_code', 'like', "{$prefix}%")->count();

        return strtoupper($prefix.'-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT));
    }

    protected function generateInvoiceNumber($property, $businessDate): string
    {
        $prefix = $property->code.'-'.$businessDate->format('Ymd');
        $count = Invoice::where('invoice_number', 'like', "{$prefix}%")->count();

        return strtoupper($prefix.'-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT));
    }

    protected function generatePaymentCode($property, $businessDate): string
    {
        $prefix = $property->code.'-'.$businessDate->format('Ymd');
        $count = Payment::where('payment_code', 'like', "{$prefix}%")->count();

        return strtoupper($prefix.'-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT));
    }
}