<?php

namespace App\Application\FrontDesk\Actions;

use App\Application\FrontDesk\Services\FrontDeskStatusRecorder;
use App\Application\Settings\Services\BusinessDateService;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Payment;
use App\Domain\FrontDesk\Models\FrontDeskAuditLog;
use App\Domain\Housekeeping\Models\HousekeepingTask;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\StayRecord;
use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\OccupancyStatus;
use App\Domain\Room\Models\Room;
use App\Domain\Room\Models\RoomStatusLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteCheckoutAction
{
    public function __construct(
        private readonly CalculateFinalBillAction $calculateFinalBillAction,
        private readonly FrontDeskStatusRecorder $statusRecorder,
        private readonly BusinessDateService $businessDateService,
    ) {}

    /**
     * Process complete checkout
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handle(Reservation $reservation, array $payload, int $actorUserId): array
    {
        $reservation->loadMissing(['assignedRoom', 'property', 'invoices', 'stayRecords']);

        if ($reservation->reservation_status !== 'checked_in') {
            throw ValidationException::withMessages([
                'reservation' => 'Reservasi tidak sedang checked_in.',
            ]);
        }

        $stayRecord = $reservation->stayRecords->first();
        if (! $stayRecord || $stayRecord->stay_status !== 'in_house') {
            throw ValidationException::withMessages([
                'stay_record' => 'Stay record tidak ditemukan atau tidak sedang in_house.',
            ]);
        }

        $actualCheckoutAt = $payload['actual_check_out_at'] ?? now();
        $damageFee = (float) ($payload['damage_fee_amount'] ?? 0);
        $lateCheckoutFee = (float) ($payload['late_checkout_fee_amount'] ?? 0);
        $paymentAmount = (float) ($payload['payment_amount'] ?? 0);
        $paymentMethodCode = $payload['payment_method_code'] ?? null;

        return DB::transaction(function () use (
            $reservation,
            $stayRecord,
            $payload,
            $actorUserId,
            $actualCheckoutAt,
            $damageFee,
            $lateCheckoutFee,
            $paymentAmount,
            $paymentMethodCode
        ) {
            $property = $reservation->property;
            $businessDate = $this->businessDateService->currentBusinessDate($property);
            $room = $reservation->assignedRoom()->lockForUpdate()->firstOrFail();

            $previousReservationStatus = $reservation->reservation_status;
            $previousRoomStatus = $room->current_status;
            $previousStayStatus = $stayRecord->stay_status;

            // Calculate final bill first
            $billData = $this->calculateFinalBillAction->handle($reservation, [
                'actual_check_out_at' => $actualCheckoutAt,
                'damage_fee_amount' => $damageFee,
                'damage_fee_notes' => $payload['damage_fee_notes'] ?? null,
                'late_checkout_hours' => $payload['late_checkout_hours'] ?? null,
                'late_checkout_hourly_rate' => 100000,
                'lost_keycard_fee' => $payload['lost_keycard_fee'] ?? 0,
            ]);

            $invoice = Invoice::find($billData['invoice']['id']);

            // Process payment if provided
            if ($paymentAmount > 0 && $paymentMethodCode) {
                $payment = Payment::query()->create([
                    'invoice_id' => $invoice->id,
                    'payment_code' => $this->generatePaymentCode($property, $businessDate),
                    'payment_type' => 'full', // or 'partial'
                    'payment_status' => 'completed',
                    'payment_method_code' => $paymentMethodCode,
                    'amount' => $paymentAmount,
                    'payment_reference' => $payload['payment_reference'] ?? null,
                    'business_date' => $businessDate->toDateString(),
                    'paid_at' => $actualCheckoutAt,
                    'received_by_user_id' => $actorUserId,
                    'notes' => $payload['notes'] ?? null,
                ]);

                // Update invoice paid amount
                $invoice->paid_amount += $paymentAmount;
                $invoice->recalculateTotals();
                $invoice->save();
            }

            // Mark invoice as issued if still draft
            if ($invoice->invoice_status === 'draft') {
                $invoice->invoice_status = 'unpaid';
                $invoice->issued_at = $actualCheckoutAt;
                $invoice->save();
            }

            // Update stay record
            $stayRecord->forceFill([
                'stay_status' => 'checked_out',
                'check_out_business_date' => $businessDate->toDateString(),
                'actual_check_out_at' => $actualCheckoutAt,
                'checked_out_by_user_id' => $actorUserId,
                'notes' => ($stayRecord->notes ?? '')."\n\n".($payload['notes'] ?? ''),
            ])->save();

            // Update reservation status
            $reservation->forceFill([
                'reservation_status' => 'checked_out',
                'checked_out_at' => $actualCheckoutAt,
            ])->save();

            // After checkout: room becomes available (occupancy) + dirty (housekeeping)
            $room->forceFill([
                'current_status' => OccupancyStatus::Available,
                'housekeeping_status' => HousekeepingStatus::Dirty,
            ])->save();

            // Create housekeeping task for checkout cleaning
            $housekeepingTask = HousekeepingTask::query()->create([
                'property_id' => $property->id,
                'room_id' => $room->id,
                'reservation_id' => $reservation->id,
                'task_type' => 'checkout_cleaning',
                'priority' => 'high', // Checkout cleaning is high priority
                'task_status' => 'pending',
                'scheduled_for' => now(),
                'issue_note' => $payload['room_condition_notes'] ?? null,
                'created_by_user_id' => $actorUserId,
            ]);

            // Record status transitions
            $this->statusRecorder->recordReservationTransition(
                $reservation,
                $previousReservationStatus,
                'checked_out',
                $actorUserId,
                $payload['notes'] ?? 'Check-out completed.',
                StayRecord::class,
                $stayRecord->id,
            );

            $this->statusRecorder->recordRoomTransition(
                $room,
                'occupancy',
                $previousRoomStatus?->value ?? $previousRoomStatus,
                OccupancyStatus::Available->value,
                $actorUserId,
                'Room available after check-out, marked dirty for cleaning.',
                StayRecord::class,
                $stayRecord->id,
            );

            // Record audit log
            FrontDeskAuditLog::query()->create([
                'reservation_id' => $reservation->id,
                'stay_record_id' => $stayRecord->id,
                'action_type' => 'checkout_completed',
                'action_label' => 'Check-out completed',
                'actor_user_id' => $actorUserId,
                'payload_json' => [
                    'room_id' => $room->id,
                    'room_number' => $room->room_number,
                    'actual_check_out_at' => $actualCheckoutAt->toIso8601String(),
                    'room_inspected' => (bool) ($payload['room_inspected'] ?? false),
                    'keycard_returned' => (bool) ($payload['keycard_returned'] ?? false),
                    'damage_fee' => $damageFee,
                    'late_checkout_fee' => $lateCheckoutFee,
                    'payment_amount' => $paymentAmount,
                ],
                'happened_at' => $actualCheckoutAt,
            ]);

            // Reload data
            $invoice->refresh();

            return [
                'reservation_id' => $reservation->id,
                'stay_record_id' => $stayRecord->id,
                'reservation_status' => $reservation->reservation_status,
                'stay_status' => $stayRecord->stay_status,
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'room_status' => $room->current_status?->value ?? $room->current_status,
                'checked_out_at' => $stayRecord->actual_check_out_at?->toISOString(),
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

    protected function generatePaymentCode(Property $property, \Carbon\Carbon $businessDate): string
    {
        $prefix = $property->code.'-'.$businessDate->format('Ymd');
        $count = Payment::where('payment_code', 'like', "{$prefix}%")->count();

        return strtoupper($prefix.'-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT));
    }
}