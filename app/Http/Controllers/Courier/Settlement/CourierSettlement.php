<?php

namespace App\Http\Controllers\Courier\Settlement;

use App\Http\Controllers\Account\Models\AcAccount;
use Illuminate\Database\Eloquent\Model;

class CourierSettlement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'settlement_date' => 'date',
    ];

    public function orders()
    {
        return $this->hasMany(CourierSettlementOrder::class, 'courier_settlement_id');
    }

    public function account()
    {
        return $this->belongsTo(AcAccount::class, 'account_id');
    }
}
