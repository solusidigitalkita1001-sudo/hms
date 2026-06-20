<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Inventory\Enums\AssetLoanReturnCondition;
use App\Domain\Reservation\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetLoan extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'asset_id',
        'staff_id',
        'loaned_at',
        'returned_at',
        'return_condition',
        'charge_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'loaned_at' => 'datetime',
            'returned_at' => 'datetime',
            'return_condition' => AssetLoanReturnCondition::class,
            'charge_amount' => 'integer',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(LoanableAsset::class, 'asset_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /**
     * Scope: only currently active (unreturned) loans.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('returned_at');
    }

    /**
     * Check if this loan has been returned.
     */
    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }
}
