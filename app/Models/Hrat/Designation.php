<?php

namespace App\Models\Hrat;

use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    protected $table = 'hrat_designations';
    protected $guarded = [];
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
