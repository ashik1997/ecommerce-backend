<?php

namespace App\Models\Hrat;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $table = 'hrat_shifts';
    protected $guarded = [];
    protected $casts = [
        'working_days' => 'array',
        'weekly_holidays' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
}
