<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function orders()
    {
        return $this->hasMany(ProductOrder::class, 'affiliate_id');
    }

    public function commissionEntries()
    {
        return $this->hasMany(SalesCommissionEntry::class, 'affiliate_id');
    }

    public function settlements()
    {
        return $this->hasMany(CommissionSettlement::class, 'affiliate_id');
    }
}
