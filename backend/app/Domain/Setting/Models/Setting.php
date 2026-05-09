<?php

namespace App\Domain\Setting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'setting_group',
        'setting_key',
        'setting_value',
    ];
}
