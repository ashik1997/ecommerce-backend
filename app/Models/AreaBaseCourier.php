<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaBaseCourier extends Model
{
    use HasFactory;
    protected $fillable = [
        'area_base_courier_id',
        'area_name',
        'shipping_cost',
        'slug',
        'creator',
        'status'
    ];

    public function courierName()
    {
        return $this->belongsTo(AreaBaseCourierName::class, 'area_base_courier_id');
    }
    
}
