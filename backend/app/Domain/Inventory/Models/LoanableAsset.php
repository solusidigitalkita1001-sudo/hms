<?php

namespace App\Domain\Inventory\Models;

use App\Domain\Property\Models\Property;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanableAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'name',
        'description',
        'total_stock',
        'available_stock',
        'condition_notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'total_stock' => 'integer',
            'available_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(AssetLoan::class, 'asset_id');
    }

    /**
     * Check if asset is available for loan.
     */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->available_stock > 0;
    }

    /**
     * Decrement available stock (when loaned out).
     */
    public function decrementStock(int $quantity = 1): void
    {
        $this->decrement('available_stock', $quantity);
    }

    /**
     * Increment available stock (when returned).
     */
    public function incrementStock(int $quantity = 1): void
    {
        $this->increment('available_stock', $quantity);
    }
}
