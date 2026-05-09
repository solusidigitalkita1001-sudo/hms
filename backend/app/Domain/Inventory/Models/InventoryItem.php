<?php

namespace App\Domain\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'category_id',
        'sku',
        'item_name',
        'unit',
        'minimum_stock',
        'current_stock',
        'last_purchase_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'minimum_stock' => 'integer',
            'current_stock' => 'integer',
            'last_purchase_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
