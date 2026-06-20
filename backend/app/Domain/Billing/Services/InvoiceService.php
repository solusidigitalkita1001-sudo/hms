<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Enums\InvoiceStatus;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceItem;
use App\Domain\Billing\Models\InvoiceStatusLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Recalculate invoice totals with audit trail
     */
    public function recalculate(Invoice $invoice, User $actor): Invoice
    {
        if ($invoice->invoice_status === InvoiceStatus::VOID->value) {
            throw new \InvalidArgumentException('Cannot recalculate voided invoice.');
        }

        $previousTotal = $invoice->grand_total;

        $invoice->recalculateTotals();
        $invoice->save();

        // Log if total changed significantly
        $difference = abs($invoice->grand_total - $previousTotal);
        if ($difference > 0.01) {
            $this->logStatusChange($invoice, $invoice->invoice_status, $invoice->invoice_status, $actor, "Recalculate: Total changed by {$difference}");
        }

        return $invoice->fresh();
    }

    /**
     * Void invoice with proper approval workflow
     */
    public function voidInvoice(Invoice $invoice, string $reason, User $actor, ?User $approvedBy = null): Invoice
    {
        if ($invoice->invoice_status === InvoiceStatus::VOID->value) {
            throw new \InvalidArgumentException('Invoice is already voided.');
        }

        if ($invoice->invoice_status === InvoiceStatus::PAID->value && $invoice->paid_amount > 0) {
            throw new \InvalidArgumentException('Cannot void paid invoice without refund process.');
        }

        return DB::transaction(function () use ($invoice, $reason, $actor, $approvedBy) {
            $previousStatus = $invoice->invoice_status;

            // Void the invoice
            $invoice->forceFill([
                'invoice_status' => InvoiceStatus::VOID->value,
                'voided_at' => now(),
                'notes' => trim(($invoice->notes ?? '')."\n\nVOID: {$reason}"),
            ])->save();

            // Log status change
            $this->logStatusChange($invoice, $previousStatus, InvoiceStatus::VOID->value, $approvedBy ?? $actor, $reason);

            // Void all associated payments
            foreach ($invoice->payments as $payment) {
                if ($payment->payment_status === 'completed') {
                    $payment->forceFill([
                        'payment_status' => 'voided',
                        'voided_at' => now(),
                        'notes' => trim(($payment->notes ?? '')."\n\nVoided via invoice: {$invoice->invoice_number}"),
                    ])->save();
                }
            }

            return $invoice->fresh(['payments', 'statusLogs']);
        });
    }

    /**
     * Add item to invoice with validation
     */
    public function addItem(Invoice $invoice, array $itemData, User $actor): InvoiceItem
    {
        if ($this->isInvoiceFinal($invoice)) {
            throw new \InvalidArgumentException('Cannot add item to final invoice.');
        }

        return DB::transaction(function () use ($invoice, $itemData, $actor) {
            $item = $invoice->items()->create([
                'item_type' => $itemData['item_type'],
                'item_code' => $itemData['item_code'] ?? null,
                'item_name' => $itemData['item_name'],
                'description' => $itemData['description'] ?? null,
                'unit_price' => $itemData['unit_price'],
                'quantity' => $itemData['quantity'],
                'discount_amount' => $itemData['discount_amount'] ?? 0,
                'tax_amount' => $itemData['tax_amount'] ?? 0,
                'item_date' => $itemData['item_date'] ?? null,
                'notes' => $itemData['notes'] ?? null,
            ]);

            $item->calculateLineTotal();
            $item->save();

            // Auto-recalculate
            $this->recalculate($invoice, $actor);

            return $item->fresh();
        });
    }

    /**
     * Update invoice item
     */
    public function updateItem(InvoiceItem $item, array $itemData, User $actor): InvoiceItem
    {
        $invoice = $item->invoice;

        if ($this->isInvoiceFinal($invoice)) {
            throw new \InvalidArgumentException('Cannot update item in final invoice.');
        }

        return DB::transaction(function () use ($item, $itemData, $actor, $invoice) {
            $item->fill([
                'item_type' => $itemData['item_type'] ?? $item->item_type,
                'item_code' => $itemData['item_code'] ?? $item->item_code,
                'item_name' => $itemData['item_name'] ?? $item->item_name,
                'description' => $itemData['description'] ?? $item->description,
                'unit_price' => $itemData['unit_price'] ?? $item->unit_price,
                'quantity' => $itemData['quantity'] ?? $item->quantity,
                'discount_amount' => $itemData['discount_amount'] ?? $item->discount_amount,
                'tax_amount' => $itemData['tax_amount'] ?? $item->tax_amount,
                'item_date' => $itemData['item_date'] ?? $item->item_date,
                'notes' => $itemData['notes'] ?? $item->notes,
            ]);

            $item->calculateLineTotal();
            $item->save();

            // Auto-recalculate
            $this->recalculate($invoice, $actor);

            return $item->fresh();
        });
    }

    /**
     * Delete invoice item
     */
    public function deleteItem(InvoiceItem $item, User $actor): void
    {
        $invoice = $item->invoice;

        if ($this->isInvoiceFinal($invoice)) {
            throw new \InvalidArgumentException('Cannot delete item from final invoice.');
        }

        DB::transaction(function () use ($item, $actor, $invoice) {
            $item->delete();
            $this->recalculate($invoice, $actor);
        });
    }

    /**
     * Check if invoice is in final state
     */
    protected function isInvoiceFinal(Invoice $invoice): bool
    {
        return in_array($invoice->invoice_status, [
            InvoiceStatus::PAID->value,
            InvoiceStatus::REFUNDED->value,
            InvoiceStatus::VOID->value,
        ], true);
    }

    /**
     * Log status change
     */
    protected function logStatusChange(Invoice $invoice, string $fromStatus, string $toStatus, User $actor, string $notes = null): void
    {
        InvoiceStatusLog::query()->create([
            'invoice_id' => $invoice->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_user_id' => $actor->id,
            'reason' => $notes,
            'changed_at' => now(),
        ]);
    }
}