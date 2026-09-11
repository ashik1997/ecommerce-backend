<?php

namespace App\Http\Controllers\Account\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\Customer\Models\Customer;
use App\Http\Controllers\Customer\Models\CustomerOpeningBalance;
use App\Models\ProductOrder;

class DbCustomerPayment extends Model
{
    use HasFactory;
    protected $guarded = []; 

    protected $table = 'db_customer_payments';

    /**
     * Get the customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the order (if linked)
     */
    public function order()
    {
        return $this->belongsTo(ProductOrder::class, 'order_id');
    }

    public function openingBalance()
    {
        return $this->belongsTo(CustomerOpeningBalance::class, 'customer_opening_balance_id');
    }
}
