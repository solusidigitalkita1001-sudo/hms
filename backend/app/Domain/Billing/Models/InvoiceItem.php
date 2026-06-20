<?php

namespace App\Domain\Billing\Models;

use App\Domain\Reservation\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'item_type',
        'item_code',
        'item_name',
        'description',
        'unit_price',
        'quantity',
        'discount_amount',
        'tax_amount',
        'line_total',
        'item_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'item_date' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Calculate line total based on unit price, quantity, and discount
     */
    public function calculateLineTotal(): void
    {
        $this->line_total = ($this->unit_price * $this->quantity) - $this->discount_amount + $this->tax_amount;
    }

    /**
     * Get subtotal (unit_price * quantity - discount)
     */
    public function getSubtotalAttribute(): float
    {
        return ($this->unit_price * $this->quantity) - $this->discount_amount;
    }
}