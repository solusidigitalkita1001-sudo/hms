<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\FrontDesk\Services\FrontDeskStatusRecorder;
use App\Domain\Billing\Models\InvoiceItem;
use App\Domain\FrontDesk\Models\FrontDeskAuditLog;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\StayRecord;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StayExtendController extends Controller
{
    public function __construct(
        private readonly FrontDeskStatusRecorder $statusRecorder,
    ) {}

    /**
     * Extend a guest's stay by updating the expected check-out date.
     */
    public function extend(Request $request, StayRecord $stayRecord): JsonResponse
    {
        $validated = $request->validate([
            'new_check_out_date' => ['required', 'date', 'after:today'],
            'additional_charge_per_night' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($stayRecord->stay_status !== 'in_house') {
            throw ValidationException::withMessages([
                'stay_record' => 'Stay record tidak sedang in_house.',
            ]);
        }

        $newCheckOut = \Carbon\Carbon::parse($validated['new_check_out_date']);
        $oldCheckOut = $stayRecord->expected_check_out_at ?? $stayRecord->reservation->check_out_date;
        $extraNights = max(1, (int) $oldCheckOut?->diffInDays($newCheckOut) ?? 1);

        DB::transaction(function () use ($stayRecord, $newCheckOut, $extraNights, $validated): void {
            // Update stay record
            $stayRecord->update([
                'expected_check_out_at' => $newCheckOut->setTime(12, 0),
                'notes' => trim(($stayRecord->notes ?? '')."\n\nExtend stay: +{$extraNights} nights. {$validated['reason']}"),
            ]);

            // Update reservation check-out date
            $stayRecord->reservation->update([
                'check_out_date' => $newCheckOut,
            ]);

            // Add extended stay charge if rate provided
            $rate = (float) ($validated['additional_charge_per_night'] ?? 0);
            if ($rate > 0) {
                $invoice = $stayRecord->reservation->invoices()
                    ->where('invoice_status', '!=', 'void')
                    ->first();

                if ($invoice) {
                    $total = $rate * $extraNights;
                    $tax = $total * 0.1;

                    InvoiceItem::query()->create([
                        'invoice_id' => $invoice->id,
                        'item_type' => 'room_charge',
                        'item_name' => "Extended Stay ({$extraNights} nights)",
                        'description' => "Extended from {$validated['reason']}",
                        'unit_price' => $rate,
                        'quantity' => $extraNights,
                        'discount_amount' => 0,
                        'tax_amount' => $tax,
                        'line_total' => $total + $tax,
                        'item_date' => now()->toDateString(),
                    ]);

                    $invoice->recalculateTotals();
                    $invoice->save();
                }
            }

            // Record audit log
            FrontDeskAuditLog::query()->create([
                'reservation_id' => $stayRecord->reservation_id,
                'stay_record_id' => $stayRecord->id,
                'action_type' => 'stay_extended',
                'action_label' => 'Stay extended',
                'actor_user_id' => auth()->id(),
                'payload_json' => [
                    'new_check_out_date' => $newCheckOut->toDateString(),
                    'extra_nights' => $extraNights,
                    'additional_charge' => $rate * $extraNights,
                    'reason' => $validated['reason'] ?? null,
                ],
                'happened_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Masa inap berhasil diperpanjang {$extraNights} malam.",
            'data' => [
                'stay_record_id' => $stayRecord->id,
                'new_check_out_date' => $newCheckOut->toDateString(),
                'extra_nights' => $extraNights,
                'additional_charge' => $validated['additional_charge_per_night']
                    ? (float) $validated['additional_charge_per_night'] * $extraNights
                    : 0,
            ],
        ]);
    }
}
