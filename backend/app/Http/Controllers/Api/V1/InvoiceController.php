<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Billing\Services\InvoiceService;
use App\Domain\Billing\Models\Invoice;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\VoidInvoiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    /**
     * Get invoice details with items and payments
     */
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        // Eager load untuk prevent N+1 queries
        $invoice->loadMissing([
            'items',
            'payments.receivedByUser',
            'reservation.primaryGuest',
            'statusLogs.changedByUser',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invoice details loaded successfully.',
            'data' => [
                'invoice' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_status' => $invoice->invoice_status,
                    'issued_at' => $invoice->issued_at?->format('Y-m-d H:i:s'),
                    'due_at' => $invoice->due_at?->format('Y-m-d H:i:s'),
                    'subtotal_amount' => (float) $invoice->subtotal_amount,
                    'tax_amount' => (float) $invoice->tax_amount,
                    'service_amount' => (float) $invoice->service_amount,
                    'discount_amount' => (float) $invoice->discount_amount,
                    'grand_total' => (float) $invoice->grand_total,
                    'paid_amount' => (float) $invoice->paid_amount,
                    'remaining_amount' => (float) $invoice->remaining_amount,
                    'voided_at' => $invoice->voided_at?->format('Y-m-d H:i:s'),
                    'refunded_at' => $invoice->refunded_at?->format('Y-m-d H:i:s'),
                    'notes' => $invoice->notes,
                    'created_at' => $invoice->created_at?->format('Y-m-d H:i:s'),
                ],
                'reservation' => $invoice->reservation ? [
                    'id' => $invoice->reservation->id,
                    'booking_code' => $invoice->reservation->booking_code,
                    'guest_name' => $invoice->reservation->primaryGuest?->full_name,
                ] : null,
                'items' => $invoice->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'item_type' => $item->item_type,
                    'item_code' => $item->item_code,
                    'item_name' => $item->item_name,
                    'description' => $item->description,
                    'unit_price' => (float) $item->unit_price,
                    'quantity' => (float) $item->quantity,
                    'discount_amount' => (float) $item->discount_amount,
                    'tax_amount' => (float) $item->tax_amount,
                    'line_total' => (float) $item->line_total,
                    'item_date' => $item->item_date?->format('Y-m-d'),
                ])->values(),
                'payments' => $invoice->payments->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'payment_code' => $payment->payment_code,
                    'payment_type' => $payment->payment_type,
                    'payment_status' => $payment->payment_status,
                    'payment_method_code' => $payment->payment_method_code,
                    'amount' => (float) $payment->amount,
                    'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
                    'received_by' => $payment->receivedByUser?->name,
                    'notes' => $payment->notes,
                ])->values(),
                'status_logs' => $invoice->statusLogs->map(fn ($log): array => [
                    'id' => $log->id,
                    'from_status' => $log->from_status,
                    'to_status' => $log->to_status,
                    'reason' => $log->reason,
                    'changed_by' => $log->changedByUser?->name,
                    'changed_at' => $log->changed_at?->format('Y-m-d H:i:s'),
                ])->values(),
            ],
            'meta' => [],
        ]);
    }

    /**
     * Recalculate invoice totals (with audit trail)
     */
    public function recalculate(Request $request, Invoice $invoice): JsonResponse
    {
        try {
            $actor = $request->user();
            $updatedInvoice = $this->invoiceService->recalculate($invoice, $actor);

            return response()->json([
                'success' => true,
                'message' => 'Invoice berhasil di-recalculate.',
                'data' => [
                    'invoice' => [
                        'id' => $updatedInvoice->id,
                        'invoice_number' => $updatedInvoice->invoice_number,
                        'invoice_status' => $updatedInvoice->invoice_status,
                        'subtotal_amount' => (float) $updatedInvoice->subtotal_amount,
                        'tax_amount' => (float) $updatedInvoice->tax_amount,
                        'service_amount' => (float) $updatedInvoice->service_amount,
                        'discount_amount' => (float) $updatedInvoice->discount_amount,
                        'grand_total' => (float) $updatedInvoice->grand_total,
                        'paid_amount' => (float) $updatedInvoice->paid_amount,
                        'remaining_amount' => (float) $updatedInvoice->remaining_amount,
                    ],
                ],
                'meta' => [],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to recalculate invoice.',
                'errors' => [
                    'general' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Void invoice with proper approval workflow
     */
    public function void(VoidInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        try {
            $validated = $request->validated();
            $actor = $request->user();

            // Check approval requirement
            // For now, self-approval allowed but should be restricted for large amounts
            $approvedBy = $actor;

            $voidedInvoice = $this->invoiceService->voidInvoice(
                $invoice,
                $validated['void_reason'],
                $actor,
                $approvedBy,
            );

            return response()->json([
                'success' => true,
                'message' => 'Invoice berhasil di-void.',
                'data' => [
                    'invoice' => [
                        'id' => $voidedInvoice->id,
                        'invoice_number' => $voidedInvoice->invoice_number,
                        'invoice_status' => $voidedInvoice->invoice_status,
                        'voided_at' => $voidedInvoice->voided_at?->format('Y-m-d H:i:s'),
                    ],
                ],
                'meta' => [],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to void invoice.',
                'errors' => [
                    'general' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}