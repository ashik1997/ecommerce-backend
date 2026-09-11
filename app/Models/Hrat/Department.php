<?php

namespace App\Models\Hrat;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'hrat_departments';
    protected $guarded = [];
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
