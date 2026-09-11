<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderExtraChargeType extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'default_amount' => 'float',
    ];
}
