<?php

namespace App\Domain\Reservation\Models;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Guest\Models\Guest;
use App\Domain\Property\Models\Property;
use App\Domain\Room\Models\RoomAvailabilityLock;
use App\Domain\Room\Models\Room;
use App\Domain\Room\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'primary_guest_id',
        'room_type_id',
        'assigned_room_id',
        'booking_code',
        'source',
        'reservation_status',
        'adult_count',
        'child_count',
        'check_in_date',
        'check_out_date',
        'rate_plan_code',
        'payment_status',
        'guarantee_status',
        'deposit_amount',
        'special_requests',
        'internal_notes',
        'booked_at',
        'expiry_at',
        'arrived_at',
        'checked_in_at',
        'checked_out_at',
        'cancelled_at',
        'expired_at',
        'no_show_at',
        'status_reason',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'adult_count' => 'integer',
            'child_count' => 'integer',
            'deposit_amount' => 'decimal:2',
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'booked_at' => 'datetime',
            'expiry_at' => 'datetime',
            'arrived_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expired_at' => 'datetime',
            'no_show_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function primaryGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'primary_guest_id');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function assignedRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'assigned_room_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reservationGuests(): HasMany
    {
        return $this->hasMany(ReservationGuest::class);
    }

    public function stayRecords(): HasMany
    {
        return $this->hasMany(StayRecord::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function checkinSessions(): HasMany
    {
        return $this->hasMany(ReservationCheckinSession::class);
    }

    public function frontDeskAuditLogs(): HasMany
    {
        return $this->hasMany(\App\Domain\FrontDesk\Models\FrontDeskAuditLog::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ReservationStatusLog::class);
    }

    public function availabilityLocks(): HasMany
    {
        return $this->hasMany(RoomAvailabilityLock::class);
    }
}
