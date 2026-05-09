<?php

namespace App\Domain\Room\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'code',
        'name',
        'capacity',
        'base_price',
        'weekend_price',
        'extra_bed_price',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'base_price' => 'decimal:2',
            'weekend_price' => 'decimal:2',
            'extra_bed_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
