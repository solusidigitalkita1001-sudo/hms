<?php

namespace App\Domain\Guest\Models;

use App\Domain\Property\Models\Property;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\ReservationGuest;
use App\Domain\Reservation\Models\StayGuest;
use App\Domain\Reservation\Models\StayRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'full_name',
        'full_name_on_id',
        'id_type',
        'id_number',
        'phone',
        'email',
        'address',
        'nationality',
        'birth_date',
        'gender',
        'notes',
        'last_stay_at',
        'total_stays',
        'identity_verified',
        'identity_verified_at',
        'identity_verified_by_user_id',
        'identity_verification_status',
        'emergency_contact_name',
        'emergency_contact_phone',
        'is_blacklisted',
        'blacklist_reason',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'last_stay_at' => 'datetime',
            'identity_verified' => 'boolean',
            'identity_verified_at' => 'datetime',
            'total_stays' => 'integer',
            'is_blacklisted' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function identityVerifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'identity_verified_by_user_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'primary_guest_id');
    }

    public function reservationGuests(): HasMany
    {
        return $this->hasMany(ReservationGuest::class);
    }

    public function stayRecords(): HasMany
    {
        return $this->hasMany(StayRecord::class, 'primary_guest_id');
    }

    public function stayGuests(): HasMany
    {
        return $this->hasMany(StayGuest::class);
    }
}
