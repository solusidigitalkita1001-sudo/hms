<?php

namespace App\Domain\FrontDesk\Models;

use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\StayRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrontDeskAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'stay_record_id',
        'action_type',
        'action_label',
        'actor_user_id',
        'payload_json',
        'happened_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'happened_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function stayRecord(): BelongsTo
    {
        return $this->belongsTo(StayRecord::class);
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
