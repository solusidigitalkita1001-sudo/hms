<?php

namespace App\Domain\Room\Models;

use App\Domain\Property\Models\Property;
use App\Domain\Room\Enums\HousekeepingStatus;
use App\Domain\Room\Enums\OccupancyStatus;
use App\Domain\Room\Enums\ServiceabilityStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'room_type_id',
        'room_number',
        'floor',
        'current_status',
        'housekeeping_status',
        'serviceability_status',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'floor' => 'integer',
            'is_active' => 'boolean',
            'current_status' => OccupancyStatus::class,
            'housekeeping_status' => HousekeepingStatus::class,
            'serviceability_status' => ServiceabilityStatus::class,
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function availabilityLocks(): HasMany
    {
        return $this->hasMany(RoomAvailabilityLock::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(RoomStatusLog::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(\App\Domain\Reservation\Models\Reservation::class, 'assigned_room_id');
    }

    /**
     * Scope to only sellable rooms.
     */
    public function scopeSellable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('current_status', OccupancyStatus::Available)
            ->whereIn('housekeeping_status', [HousekeepingStatus::Clean, HousekeepingStatus::Inspected])
            ->where('serviceability_status', ServiceabilityStatus::Normal);
    }

    /**
     * Check if this room can be sold (booked) right now.
     */
    public function isSellable(): bool
    {
        return $this->is_active
            && $this->current_status === OccupancyStatus::Available
            && in_array($this->housekeeping_status, [HousekeepingStatus::Clean, HousekeepingStatus::Inspected], true)
            && $this->serviceability_status === ServiceabilityStatus::Normal;
    }

    /**
     * Get a human-readable summary of the room's composite status.
     */
    public function getCompositeStatusLabel(): string
    {
        $parts = [];

        $parts[] = match ($this->current_status->value ?? $this->current_status) {
            'available' => 'Available',
            'reserved' => 'Reserved',
            'occupied' => 'Occupied',
            default => ucfirst($this->current_status->value ?? $this->current_status),
        };

        $hk = $this->housekeeping_status->value ?? $this->housekeeping_status;
        if ($hk !== 'clean') {
            $parts[] = match ($hk) {
                'dirty' => 'Dirty',
                'inspected' => 'Inspected',
                default => ucfirst($hk),
            };
        }

        $svc = $this->serviceability_status->value ?? $this->serviceability_status;
        if ($svc !== 'normal') {
            $parts[] = match ($svc) {
                'maintenance' => 'Maintenance',
                'out_of_service' => 'Out of Service',
                default => ucfirst($svc),
            };
        }

        return implode(' · ', $parts);
    }

    /**
     * Priority level for UI display (lower = more urgent attention needed).
     * Returns 0 (occupied) through 5 (available).
     */
    public function getStatusPriority(): int
    {
        // Out of service / maintenance → highest priority
        if ($this->serviceability_status !== ServiceabilityStatus::Normal) {
            return 0;
        }

        return match ($this->current_status) {
            OccupancyStatus::Occupied => 1,
            OccupancyStatus::Reserved => 2,
            OccupancyStatus::Available => match ($this->housekeeping_status) {
                HousekeepingStatus::Dirty => 3,
                HousekeepingStatus::Inspected => 4,
                default => 5,
            },
        };
    }
}
