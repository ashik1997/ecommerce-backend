<?php

namespace App\Models\ServiceManagement;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class ServicePayment extends Model
{
    protected $table = 'srms_service_payments';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function serviceInstance()
    {
        return $this->belongsTo(ServiceInstance::class, 'service_instance_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function paymentType()
    {
        return $this->belongsTo(DbPaymentType::class, 'payment_type_id');
    }

    public function account()
    {
        return $this->belongsTo(AcAccount::class, 'account_id');
    }
}
