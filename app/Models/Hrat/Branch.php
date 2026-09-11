<?php

namespace App\Models\Hrat;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $table = 'hrat_branches';
    protected $guarded = [];
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
