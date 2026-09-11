<?php

namespace App\Models;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOrderRefund extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function return()
    {
        return $this->belongsTo(ProductOrderReturn::class, 'product_order_return_id');
    }

    public function order()
    {
        return $this->belongsTo(ProductOrder::class, 'product_order_id');
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
