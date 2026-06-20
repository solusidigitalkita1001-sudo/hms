<?php

namespace App\Application\FrontDesk\Actions;

use App\Application\Settings\Services\BusinessDateService;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Payment;
use App\Domain\Property\Models\Property;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\StayRecord;
use App\Domain\Room\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CalculateFinalBillAction
{
    public function __construct(
        private readonly BusinessDateService $businessDateService,
    ) {}

    /**
     * Calculate final bill for checkout preview
     *
     * @param  array<string, mixed>  $additionalCharges
     * @return array<string, mixed>
     */
    public function handle(Reservation $reservation, array $additionalCharges = []): array
    {
        $reservation->loadMissing(['assignedRoom', 'primaryGuest', 'invoices.items', 'invoices.payments', 'stayRecords']);

        if ($reservation->reservation_status !== 'checked_in') {
            throw new \InvalidArgumentException('Reservasi tidak sedang checked_in.');
        }

        $stayRecord = $reservation->stayRecords->first();
        if (! $stayRecord || $stayRecord->stay_status !== 'in_house') {
            throw new \InvalidArgumentException('Stay record tidak ditemukan atau tidak sedang in_house.');
        }

        $property = $reservation->property;
        $businessDate = $this->businessDateService->currentBusinessDate($property);
        $room = $reservation->assignedRoom;

        // Calculate nights stayed
        $checkInDate = $stayRecord->actual_check_in_at;
        $checkOutDate = $additionalCharges['actual_check_out_at']
            ? Carbon::parse($additionalCharges['actual_check_out_at'])
            : Carbon::now();
        $nightsStayed = $checkInDate->diffInDays($checkOutDate);
        $nightsStayed = max(1, $nightsStayed); // Minimum 1 night

        // Calculate late checkout fee
        $expectedCheckout = $stayRecord->expected_check_out_at ?? $reservation->check_out_date?->setTime(12, 0);
        $lateCheckoutHours = 0;
        $lateCheckoutFee = 0;

        if ($expectedCheckout && $checkOutDate->gt($expectedCheckout)) {
            $lateCheckoutHours = ceil($checkOutDate->diffInHours($expectedCheckout));
            $lateCheckoutFee = ($lateCheckoutHours * ($additionalCharges['late_checkout_hourly_rate'] ?? 100000)); // Default Rp 100k/hour
        }

        // Get existing invoice
        $invoice = $reservation->invoices->firstWhere('invoice_status', '!=', 'void');

        if (! $invoice) {
            // Create draft invoice if doesn't exist
            $invoice = Invoice::query()->create([
                'reservation_id' => $reservation->id,
                'invoice_number' => $this->generateInvoiceNumber($property, $businessDate),
                'invoice_status' => 'draft',
                'issued_at' => $checkOutDate,
                'created_by_user_id' => auth()->id(),
            ]);
        }

        // Calculate room charge if not already charged
        $existingRoomCharge = $invoice->items->where('item_type', 'room_charge')->sum('line_total');

        if ($existingRoomCharge === 0 && $reservation->roomType) {
            $baseRate = $reservation->roomType->base_price ?? 500000;
            $roomChargeTotal = $baseRate * $nightsStayed;

            // Add room charge to invoice
            $invoice->items()->create([
                'item_type' => 'room_charge',
                'item_name' => "Room Charge ({$nightsStayed} nights)",
                'description' => "Room {$room->room_number} - {$reservation->roomType->name}",
                'unit_price' => $baseRate,
                'quantity' => $nightsStayed,
                'discount_amount' => 0,
                'tax_amount' => $roomChargeTotal * 0.1, // 10% tax
                'line_total' => $roomChargeTotal * 1.1,
                'item_date' => $checkInDate,
            ]);
        }

        // Add damage fee if any
        $damageFee = (float) ($additionalCharges['damage_fee_amount'] ?? 0);
        if ($damageFee > 0) {
            $invoice->items()->create([
                'item_type' => 'damage_fee',
                'item_name' => 'Damage Fee',
                'description' => $additionalCharges['damage_fee_notes'] ?? null,
                'unit_price' => $damageFee,
                'quantity' => 1,
                'discount_amount' => 0,
                'tax_amount' => $damageFee * 0.1,
                'line_total' => $damageFee * 1.1,
                'item_date' => $checkOutDate,
            ]);
        }

        // Add late checkout fee if any
        if ($lateCheckoutFee > 0) {
            $invoice->items()->create([
                'item_type' => 'late_checkout_fee',
                'item_name' => "Late Checkout Fee ({$lateCheckoutHours} hours)",
                'description' => "Checkout after {$expectedCheckout?->format('H:i')}",
                'unit_price' => $lateCheckoutFee,
                'quantity' => 1,
                'discount_amount' => 0,
                'tax_amount' => $lateCheckoutFee * 0.1,
                'line_total' => $lateCheckoutFee * 1.1,
                'item_date' => $checkOutDate,
            ]);
        }

        // Add lost keycard fee if any
        $lostKeycardFee = (float) ($additionalCharges['lost_keycard_fee'] ?? 0);
        if ($lostKeycardFee > 0) {
            $invoice->items()->create([
                'item_type' => 'lost_keycard_fee',
                'item_name' => 'Lost Keycard Fee',
                'description' => 'Replacement fee for lost keycard',
                'unit_price' => $lostKeycardFee,
                'quantity' => 1,
                'discount_amount' => 0,
                'tax_amount' => $lostKeycardFee * 0.1,
                'line_total' => $lostKeycardFee * 1.1,
                'item_date' => $checkOutDate,
            ]);
        }

        // Recalculate invoice totals
        $invoice->recalculateTotals();
        $invoice->refresh();

        // Calculate payment summary
        $payments = $invoice->payments;
        $totalPaid = $payments->sum('amount');
        $depositPaid = $reservation->deposit_amount ?? 0;
        $remaining = $invoice->grand_total - $totalPaid - $depositPaid;

        return [
            'reservation' => [
                'id' => $reservation->id,
                'booking_code' => $reservation->booking_code,
                'check_in_date' => $stayRecord->actual_check_in_at?->format('Y-m-d H:i:s'),
                'check_out_date' => $checkOutDate->format('Y-m-d H:i:s'),
                'nights_stayed' => $nightsStayed,
                'expected_check_out' => $expectedCheckout?->format('Y-m-d H:i:s'),
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
                'paid_amount' => (float) $totalPaid,
                'remaining_amount' => (float) max(0, $remaining),
            ],
            'payments' => $payments->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'payment_code' => $payment->payment_code,
                'payment_method_code' => $payment->payment_method_code,
                'amount' => (float) $payment->amount,
                'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
            ])->values(),
            'additional_charges' => [
                'damage_fee' => (float) $damageFee,
                'damage_fee_notes' => $additionalCharges['damage_fee_notes'] ?? null,
                'late_checkout_hours' => $lateCheckoutHours,
                'late_checkout_fee' => (float) $lateCheckoutFee,
                'lost_keycard_fee' => (float) $lostKeycardFee,
            ],
            'business_date' => $businessDate->toDateString(),
        ];
    }

    protected function generateInvoiceNumber(Property $property, Carbon $businessDate): string
    {
        $prefix = $property->code.'-'.$businessDate->format('Ymd');
        $count = Invoice::where('invoice_number', 'like', "{$prefix}%")->count();

        return strtoupper($prefix.'-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT));
    }
}