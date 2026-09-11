<?php

namespace App\Models\Hrat;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $table = 'hrat_holidays';
    protected $guarded = [];
    protected $casts = [
        'holiday_date' => 'date',
        'is_working_day_override' => 'boolean',
        'is_active' => 'boolean',
    ];
}
