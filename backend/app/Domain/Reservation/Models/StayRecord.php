<?php

namespace App\Domain\Reservation\Models;

use App\Domain\FrontDesk\Models\FrontDeskAuditLog;
use App\Domain\Guest\Models\Guest;
use App\Domain\Property\Models\Property;
use App\Domain\Room\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StayRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'property_id',
        'room_id',
        'primary_guest_id',
        'stay_status',
        'check_in_business_date',
        'actual_check_in_at',
        'check_out_business_date',
        'actual_check_out_at',
        'expected_check_out_at',
        'checked_in_by_user_id',
        'checked_out_by_user_id',
        'primary_guest_name_snapshot',
        'registration_signed',
        'registration_signed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'check_in_business_date' => 'date',
            'actual_check_in_at' => 'datetime',
            'check_out_business_date' => 'date',
            'actual_check_out_at' => 'datetime',
            'expected_check_out_at' => 'datetime',
            'registration_signed' => 'boolean',
            'registration_signed_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function primaryGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'primary_guest_id');
    }

    public function checkedInByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by_user_id');
    }

    public function checkedOutByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by_user_id');
    }

    public function frontDeskAuditLogs(): HasMany
    {
        return $this->hasMany(FrontDeskAuditLog::class);
    }

    public function stayGuests(): HasMany
    {
        return $this->hasMany(StayGuest::class);
    }
}
