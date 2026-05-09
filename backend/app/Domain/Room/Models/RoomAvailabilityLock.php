<?php

namespace App\Domain\Room\Models;

use App\Domain\Property\Models\Property;
use App\Domain\Reservation\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomAvailabilityLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'room_id',
        'reservation_id',
        'locked_by_user_id',
        'lock_source',
        'expires_at',
        'released_at',
        'release_reason',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }
}
