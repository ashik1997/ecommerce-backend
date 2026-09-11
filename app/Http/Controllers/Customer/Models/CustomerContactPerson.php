<?php

namespace App\Http\Controllers\Customer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerContactPerson extends Model
{
    use HasFactory;
    
    protected $table = 'customer_contact_persons';

    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
