<?php

namespace App\Domain\Reservation\Models;

use App\Domain\Guest\Models\Guest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StayGuest extends Model
{
    use HasFactory;

    protected $fillable = [
        'stay_record_id',
        'guest_id',
        'is_primary',
        'occupancy_role',
        'identity_verified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'identity_verified_at' => 'datetime',
        ];
    }

    public function stayRecord(): BelongsTo
    {
        return $this->belongsTo(StayRecord::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
