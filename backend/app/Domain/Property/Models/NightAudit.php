<?php

namespace App\Domain\Property\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NightAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'business_date',
        'next_business_date',
        'status',
        'closed_by_user_id',
        'started_at',
        'completed_at',
        'notes',
        'summary_json',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'next_business_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'summary_json' => 'array',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }
}
