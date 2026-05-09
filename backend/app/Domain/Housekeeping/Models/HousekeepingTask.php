<?php

namespace App\Domain\Housekeeping\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HousekeepingTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'room_id',
        'reservation_id',
        'assigned_employee_id',
        'task_type',
        'priority',
        'task_status',
        'scheduled_for',
        'started_at',
        'completed_at',
        'verified_at',
        'verified_by_user_id',
        'issue_note',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
