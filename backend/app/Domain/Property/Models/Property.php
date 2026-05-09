<?php

namespace App\Domain\Property\Models;

use App\Domain\Room\Models\RoomAvailabilityLock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'email',
        'timezone',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function roomAvailabilityLocks(): HasMany
    {
        return $this->hasMany(RoomAvailabilityLock::class);
    }
}
