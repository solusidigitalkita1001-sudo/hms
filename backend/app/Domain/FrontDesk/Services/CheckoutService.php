<?php

namespace App\Domain\FrontDesk\Services;

use App\Application\FrontDesk\Services\FrontDeskStatusRecorder;
use App\Application\Settings\Services\BusinessDateService;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceItem;
use App\Domain\Billing\Models\Payment;
use App\Domain\FrontDesk\Models\FrontDeskAuditLog;
use App\Domain\Housekeeping\Models\HousekeepingTask;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\StayRecord;
use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\OccupancyStatus;
use App\Domain\Room\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        private readonly BusinessDateService $businessDateService,
        private readonly FrontDeskStatusRecorder $statusRecorder,
    ) {}

    /**
     * Calculate final bill for checkout preview
     *
     * @param  array<string, mixed>  $additionalCharges
     * @return array<string, mixed>
     */
    public function calculateFinalBill(Reservation $reservation, array $additionalCharges = []): array
    {
        if ($reservation->reservation_status !== 'checked_in') {
            throw new \InvalidArgumentException('Reservation is not checked in.');
        }

        $reservation->loadMissing(['assignedRoom', 'primaryGuest', 'invoices.items', 'invoices.payments', 'stayRecords']);

        $stayRecord = $reservation->stayRecords->first();
        if (! $stayRecord || $stayRecord->stay_status !== 'in_house') {
            throw new \InvalidArgumentException('Stay record not found or not in_house.');
        }

        $property = $reservation->property;
        $businessDate = $this->businessDateService->currentBusinessDate($property);
        $room = $reservation->assignedRoom;

        // Calculate nights stayed
        $checkInDate = $stayRecord->actual_check_in_at;
        $checkOutDate = $additionalCharges['actual_check_out_at']
            ? \Carbon\Carbon::parse($additionalCharges['actual_check_out_at'])
            : \Carbon\Carbon::now();
        $nightsStayed = max(1, $checkInDate->diffInDays($checkOutDate));

        // Calculate late checkout fee
        $lateCheckoutHours = 0;
        $lateCheckoutFee = 0;
        $expectedCheckout = $stayRecord->expected_check_out_at
            ?? $reservation->check_out_date?->setTime(12, 0);

        if ($expectedCheckout && $checkOutDate->gt($expectedCheckout)) {
            $lateCheckoutHours = ceil($checkOutDate->diffInHours($expectedCheckout));
            $lateCheckoutFee = $lateCheckoutHours * ($additionalCharges['late_checkout_hourly_rate'] ?? 100000);
        }

        // Get or create invoice
        $invoice = $this->getOrCreateInvoice($reservation, $property, $businessDate, $checkOutDate);

        // Add room charge if not exists
        $this->addRoomChargeIfNotExists($invoice, $room, $reservation, $nightsStayed, $checkInDate);

        // Add additional charges
        $this->addAdditionalCharges($invoice, [
            'damage_fee_amount' => $additionalCharges['damage_fee_amount'] ?? 0,
            'damage_fee_notes' => $additionalCharges['damage_fee_notes'] ?? null,
            'late_checkout_fee' => $lateCheckoutFee,
            'late_checkout_hours' => $lateCheckoutHours,
            'expected_checkout' => $expectedCheckout,
            'lost_keycard_fee' => $additionalCharges['lost_keycard_fee'] ?? 0,
        ], $checkOutDate);

        // Recalculate
        $invoice->recalculateTotals();
        $invoice->save();

        return $this->formatBillData($reservation, $stayRecord, $room, $invoice, $businessDate, [
            'nights_stayed' => $nightsStayed,
            'actual_check_out_at' => $checkOutDate->format('Y-m-d H:i:s'),
            'late_checkout_hours' => $lateCheckoutHours,
            'late_checkout_fee' => $lateCheckoutFee,
            'damage_fee_amount' => $additionalCharges['damage_fee_amount'] ?? 0,
            'damage_fee_notes' => $additionalCharges['damage_fee_notes'] ?? null,
            'lost_keycard_fee' => $additionalCharges['lost_keycard_fee'] ?? 0,
        ]);
    }

    /**
     * Complete checkout process
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function completeCheckout(Reservation $reservation, array $payload, User $actor): array
    {
        if ($reservation->reservation_status !== 'checked_in') {
            throw new \InvalidArgumentException('Reservation is not checked in.');
        }

        return DB::transaction(function () use ($reservation, $payload, $actor) {
            $reservation->loadMissing(['assignedRoom', 'property', 'invoices', 'stayRecords']);

            $stayRecord = $reservation->stayRecords->first();
            if (! $stayRecord || $stayRecord->stay_status !== 'in_house') {
                throw new \InvalidArgumentException('Stay record not found or not in_house.');
            }

            $actualCheckoutAt = $payload['actual_check_out_at'] ?? now();
            $businessDate = $this->businessDateService->currentBusinessDate($reservation->property);
            $room = $reservation->assignedRoom()->lockForUpdate()->firstOrFail();

            // Calculate final bill first
            $billData = $this->calculateFinalBill($reservation, [
                'actual_check_out_at' => $actualCheckoutAt,
                'damage_fee_amount' => $payload['damage_fee_amount'] ?? 0,
                'damage_fee_notes' => $payload['damage_fee_notes'] ?? null,
                'late_checkout_hours' => $payload['late_checkout_hours'] ?? null,
                'late_checkout_hourly_rate' => $payload['late_checkout_hourly_rate'] ?? 100000,
                'lost_keycard_fee' => $payload['lost_keycard_fee'] ?? 0,
            ]);

            $invoice = Invoice::find($billData['invoice']['id']);

            // Process payment if provided
            if (! empty($payload['payment_amount']) && ! empty($payload['payment_method_code'])) {
                $this->processPayment($invoice, $payload, $actualCheckoutAt, $actor, $businessDate);
            }

            // Mark invoice as issued if still draft
            if ($invoice->invoice_status === 'draft') {
                $invoice->update(['invoice_status' => 'unpaid', 'issued_at' => $actualCheckoutAt]);
            }

            // Update stay record
            $this->updateStayRecord($stayRecord, $actualCheckoutAt, $businessDate, $actor, $payload);

            // Update reservation
            $this->updateReservation($reservation, $actualCheckoutAt);

            // Update room status
            $this->updateRoomStatus($room, $actualCheckoutAt);

            // Create housekeeping task
            $housekeepingTask = $this->createHousekeepingTask($reservation, $room, $payload, $actor);

            // Record audit log
            $this->recordAuditLog($reservation, $stayRecord, $room, $payload, $actor, $actualCheckoutAt);

            // Return formatted result
            return [
                'reservation_id' => $reservation->id,
                'stay_record_id' => $stayRecord->id,
                'reservation_status' => $reservation->reservation_status,
                'stay_status' => $stayRecord->stay_status,
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'room_status' => $room->current_status?->value ?? $room->current_status,
                'checked_out_at' => $stayRecord->fresh()->actual_check_out_at?->toISOString(),
                'housekeeping_task_id' => $housekeepingTask->id,
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_status' => $invoice->invoice_status,
                    'subtotal_amount' => (float) $invoice->subtotal_amount,
                    'tax_amount' => (float) $invoice->tax_amount,
                    'grand_total' => (float) $invoice->grand_total,
                    'paid_amount' => (float) $invoice->paid_amount,
                    'remaining_amount' => (float) $invoice->remaining_amount,
                ],
                'business_date' => $businessDate->toDateString(),
            ];
        });
    }

    /**
     * Get or create invoice for reservation
     */
    protected function getOrCreateInvoice(Reservation $reservation, $property, $businessDate, $checkOutDate): Invoice
    {
        $invoice = $reservation->invoices()->where('invoice_status', '!=', 'void')->first();

        if (! $invoice) {
            $invoice = Invoice::query()->create([
                'reservation_id' => $reservation->id,
                'invoice_number' => $this->generateInvoiceNumber($property, $businessDate),
                'invoice_status' => 'draft',
                'issued_at' => $checkOutDate,
                'created_by_user_id' => auth()->id(),
            ]);
        }

        return $invoice;
    }

    /**
     * Add or update room charge if not already charged
     */
    protected function addRoomChargeIfNotExists(Invoice $invoice, $room, $reservation, $nightsStayed, $checkInDate): void
    {
        if (! $reservation->roomType) {
            return;
        }

        $baseRate = $reservation->roomType->base_price ?? 500000;
        $roomChargeTotal = $baseRate * $nightsStayed;

        $invoice->items()->updateOrCreate(
            ['item_type' => 'room_charge'],
            [
                'item_name' => "Room Charge ({$nightsStayed} nights)",
                'description' => "Room {$room->room_number} - {$reservation->roomType->name}",
                'unit_price' => $baseRate,
                'quantity' => $nightsStayed,
                'discount_amount' => 0,
                'tax_amount' => $roomChargeTotal * 0.1,
                'line_total' => $roomChargeTotal * 1.1,
                'item_date' => $checkInDate,
            ],
        );
    }

    /**
     * Add or update additional charges (damage fee, late checkout, etc).
     * Uses updateOrCreate per type to avoid duplicates on repeated preview calls.
     */
    protected function addAdditionalCharges(Invoice $invoice, array $charges, $checkOutDate): void
    {
        // Damage fee — update or delete existing
        $damageAmount = (float) ($charges['damage_fee_amount'] ?? 0);
        $existingDamage = $invoice->items()->where('item_type', 'damage_fee')->first();

        if ($damageAmount > 0) {
            $description = $charges['damage_fee_notes'] ?? null;
            $invoice->items()->updateOrCreate(
                ['item_type' => 'damage_fee'],
                [
                    'item_name' => 'Damage Fee',
                    'description' => $description,
                    'unit_price' => $damageAmount,
                    'quantity' => 1,
                    'discount_amount' => 0,
                    'tax_amount' => $damageAmount * 0.1,
                    'line_total' => $damageAmount * 1.1,
                    'item_date' => $checkOutDate,
                ],
            );
        } elseif ($existingDamage) {
            $existingDamage->delete();
        }

        // Late checkout fee — update or delete existing
        $lateFee = (float) ($charges['late_checkout_fee'] ?? 0);
        $existingLateFee = $invoice->items()->where('item_type', 'late_checkout_fee')->first();

        if ($lateFee > 0) {
            $hours = $charges['late_checkout_hours'] ?? 0;
            $expected = isset($charges['expected_checkout']) && $charges['expected_checkout'] instanceof \Carbon\Carbon
                ? $charges['expected_checkout']->format('H:i')
                : '12:00';
            $invoice->items()->updateOrCreate(
                ['item_type' => 'late_checkout_fee'],
                [
                    'item_name' => "Late Checkout Fee ({$hours} hours)",
                    'description' => "Checkout after {$expected}",
                    'unit_price' => $lateFee,
                    'quantity' => 1,
                    'discount_amount' => 0,
                    'tax_amount' => $lateFee * 0.1,
                    'line_total' => $lateFee * 1.1,
                    'item_date' => $checkOutDate,
                ],
            );
        } elseif ($existingLateFee) {
            $existingLateFee->delete();
        }

        // Lost keycard fee — update or delete existing
        $keycardAmount = (float) ($charges['lost_keycard_fee'] ?? 0);
        $existingKeycard = $invoice->items()->where('item_type', 'lost_keycard_fee')->first();

        if ($keycardAmount > 0) {
            $invoice->items()->updateOrCreate(
                ['item_type' => 'lost_keycard_fee'],
                [
                    'item_name' => 'Lost Keycard Fee',
                    'description' => 'Replacement fee for lost keycard',
                    'unit_price' => $keycardAmount,
                    'quantity' => 1,
                    'discount_amount' => 0,
                    'tax_amount' => $keycardAmount * 0.1,
                    'line_total' => $keycardAmount * 1.1,
                    'item_date' => $checkOutDate,
                ],
            );
        } elseif ($existingKeycard) {
            $existingKeycard->delete();
        }
    }

    /**
     * Process payment
     */
    protected function processPayment(Invoice $invoice, array $payload, $actualCheckoutAt, $actor, $businessDate): void
    {
        $payment = Payment::query()->create([
            'invoice_id' => $invoice->id,
            'payment_code' => $this->generatePaymentCode($invoice->reservation->property, $businessDate),
            'payment_type' => 'full',
            'payment_status' => 'completed',
            'payment_method_code' => $payload['payment_method_code'],
            'amount' => $payload['payment_amount'],
            'payment_reference' => $payload['payment_reference'] ?? null,
            'business_date' => $businessDate->toDateString(),
            'paid_at' => $actualCheckoutAt,
            'received_by_user_id' => $actor->id,
            'notes' => $payload['notes'] ?? null,
        ]);

        $invoice->update(['paid_amount' => $invoice->paid_amount + $payment->amount]);
        $invoice->recalculateTotals();
        $invoice->save();
    }

    /**
     * Update stay record
     */
    protected function updateStayRecord(StayRecord $stayRecord, $actualCheckoutAt, $businessDate, $actor, $payload): void
    {
        $stayRecord->forceFill([
            'stay_status' => 'checked_out',
            'check_out_business_date' => $businessDate->toDateString(),
            'actual_check_out_at' => $actualCheckoutAt,
            'checked_out_by_user_id' => $actor->id,
            'notes' => trim(($stayRecord->notes ?? '')."\n\n".($payload['notes'] ?? '')),
        ])->save();
    }

    /**
     * Update reservation status
     */
    protected function updateReservation(Reservation $reservation, $actualCheckoutAt): void
    {
        $reservation->forceFill([
            'reservation_status' => 'checked_out',
            'checked_out_at' => $actualCheckoutAt,
        ])->save();
    }

    /**
     * Update room status
     */
    protected function updateRoomStatus(Room $room, $actualCheckoutAt): void
    {
        // After checkout: room becomes available (occupancy) + dirty (housekeeping)
        $room->forceFill([
            'current_status' => OccupancyStatus::Available,
            'housekeeping_status' => HousekeepingStatus::Dirty,
        ])->save();
    }

    /**
     * Create housekeeping task
     */
    protected function createHousekeepingTask(Reservation $reservation, $room, $payload, $actor): HousekeepingTask
    {
        return HousekeepingTask::query()->create([
            'property_id' => $reservation->property_id,
            'room_id' => $room->id,
            'reservation_id' => $reservation->id,
            'task_type' => 'checkout_cleaning',
            'priority' => 'high',
            'task_status' => 'pending',
            'scheduled_for' => now(),
            'issue_note' => $payload['room_condition_notes'] ?? null,
            'created_by_user_id' => $actor->id,
        ]);
    }

    /**
     * Record audit log
     */
    protected function recordAuditLog(Reservation $reservation, StayRecord $stayRecord, $room, $payload, $actor, $actualCheckoutAt): void
    {
        FrontDeskAuditLog::query()->create([
            'reservation_id' => $reservation->id,
            'stay_record_id' => $stayRecord->id,
            'action_type' => 'checkout_completed',
            'action_label' => 'Check-out completed',
            'actor_user_id' => $actor->id,
            'payload_json' => [
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'actual_check_out_at' => $actualCheckoutAt->toIso8601String(),
                'room_inspected' => (bool) ($payload['room_inspected'] ?? false),
                'keycard_returned' => (bool) ($payload['keycard_returned'] ?? false),
                'damage_fee' => $payload['damage_fee_amount'] ?? 0,
                'payment_amount' => $payload['payment_amount'] ?? 0,
            ],
            'happened_at' => $actualCheckoutAt,
        ]);
    }

    /**
     * Format bill data for response
     */
    protected function formatBillData($reservation, $stayRecord, $room, $invoice, $businessDate, $extraData): array
    {
        $payments = $invoice->payments;
        $depositPaid = $reservation->deposit_amount ?? 0;
        $remaining = $invoice->grand_total - $payments->sum('amount') - $depositPaid;

        return [
            'reservation' => [
                'id' => $reservation->id,
                'booking_code' => $reservation->booking_code,
                'check_in_date' => $stayRecord->actual_check_in_at?->format('Y-m-d H:i:s'),
                'check_out_date' => $extraData['actual_check_out_at'] ?? $stayRecord->actual_check_out_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
                'nights_stayed' => $extraData['nights_stayed'] ?? 0,
                'expected_check_out' => $stayRecord->expected_check_out_at?->format('Y-m-d H:i:s'),
                'deposit_amount' => $depositPaid,
            ],
            'room' => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'room_type' => $reservation->roomType?->name,
            ],
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_status' => $invoice->invoice_status,
                'items' => $invoice->items->map(fn ($item) => [
                    'id' => $item->id,
                    'item_type' => $item->item_type,
                    'item_name' => $item->item_name,
                    'description' => $item->description,
                    'unit_price' => (float) $item->unit_price,
                    'quantity' => (float) $item->quantity,
                    'discount_amount' => (float) $item->discount_amount,
                    'tax_amount' => (float) $item->tax_amount,
                    'line_total' => (float) $item->line_total,
                ])->values(),
                'subtotal_amount' => (float) $invoice->subtotal_amount,
                'tax_amount' => (float) $invoice->tax_amount,
                'discount_amount' => (float) $invoice->discount_amount,
                'grand_total' => (float) $invoice->grand_total,
                'paid_amount' => (float) $invoice->paid_amount,
                'remaining_amount' => (float) max(0, $remaining),
            ],
            'payments' => $payments->map(fn ($payment) => [
                'id' => $payment->id,
                'payment_code' => $payment->payment_code,
                'payment_method_code' => $payment->payment_method_code,
                'amount' => (float) $payment->amount,
                'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
            ])->values(),
            'additional_charges' => [
                'damage_fee' => (float) ($extraData['damage_fee_amount'] ?? 0),
                'damage_fee_notes' => $extraData['damage_fee_notes'] ?? null,
                'late_checkout_hours' => (int) ($extraData['late_checkout_hours'] ?? 0),
                'late_checkout_fee' => (float) ($extraData['late_checkout_fee'] ?? 0),
                'lost_keycard_fee' => (float) ($extraData['lost_keycard_fee'] ?? 0),
            ],
            'business_date' => $businessDate->toDateString(),
        ];
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