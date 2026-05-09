<?php

namespace App\Domain\Reservation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationCheckinSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'arrival_status',
        'current_step',
        'id_verification_status',
        'registration_status',
        'signature_status',
        'deposit_status',
        'override_reason',
        'override_approved_by_user_id',
        'started_by_user_id',
        'completed_by_user_id',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function overrideApprovedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'override_approved_by_user_id');
    }

    public function startedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }
}
