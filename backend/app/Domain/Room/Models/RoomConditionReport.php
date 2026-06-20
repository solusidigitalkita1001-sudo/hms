<?php

namespace App\Domain\Room\Models;

use App\Domain\Reservation\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomConditionReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reservation_id',
        'room_id',
        'reported_by',
        'reporter_type',
        'guest_name',
        'report_time',
        'window_expired_at',
        'items',
        'acknowledged_by',
        'acknowledged_at',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'report_time' => 'datetime',
            'window_expired_at' => 'datetime',
            'items' => 'array',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Scope: reports that were made within the valid window.
     */
    public function scopeWithinWindow($query)
    {
        return $query->whereNull('window_expired_at')
            ->orWhere('window_expired_at', '>=', now());
    }

    /**
     * Check if this report was made within the valid window.
     */
    public function isWithinWindow(): bool
    {
        if ($this->window_expired_at === null) {
            return true;
        }

        return now()->lte($this->window_expired_at);
    }

    /**
     * Check if the report has been acknowledged by staff.
     */
    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }
}
