<?php

namespace App\Domain\Billing\Models;

use App\Domain\Reservation\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'invoice_number',
        'invoice_status',
        'issued_at',
        'subtotal_amount',
        'tax_amount',
        'service_amount',
        'discount_amount',
        'grand_total',
        'paid_amount',
        'remaining_amount',
        'due_at',
        'notes',
        'voided_at',
        'refunded_at',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'subtotal_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'service_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'voided_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(InvoiceStatusLog::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Recalculate invoice totals based on items
     */
    public function recalculateTotals(): void
    {
        $items = $this->items;

        $this->subtotal_amount = $items->sum(fn (InvoiceItem $item) => $item->getSubtotalAttribute());
        $this->tax_amount = $items->sum('tax_amount');
        $this->discount_amount = $items->sum('discount_amount');
        $this->grand_total = $this->subtotal_amount + $this->tax_amount;
        $this->remaining_amount = $this->grand_total - $this->paid_amount;

        $this->updateInvoiceStatus();
    }

    /**
     * Update invoice status based on payment
     */
    protected function updateInvoiceStatus(): void
    {
        if ($this->paid_amount >= $this->grand_total) {
            $this->invoice_status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->invoice_status = 'partial';
        } else {
            $this->invoice_status = 'unpaid';
        }
    }
}
