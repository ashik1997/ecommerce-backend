<?php

namespace App\Http\Controllers\Customer\Models;

use App\Http\Controllers\Outlet\Models\CustomerSourceType;
use App\Http\Controllers\Account\Models\DbCustomerPayment;
use App\Models\BillingAddress;
use App\Models\Crm\CrmActivity;
use App\Models\Crm\CrmCommunication;
use App\Models\Crm\CrmLead;
use App\Models\Crm\CrmNote;
use App\Models\Crm\CrmTask;
use App\Models\Crm\CustomerTag;
use App\Models\ProductOrderRefund;
use App\Models\ProductOrderReturn;
use App\Models\ShippingInfo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function customerCategory()
    {
        return $this->belongsTo(CustomerCategory::class, 'customer_category_id');
    }

    public function customerSourceType()
    {
        return $this->belongsTo(CustomerSourceType::class, 'customer_source_type_id');
    }

    public function referenceBy()
    {
        return $this->belongsTo(User::class, 'reference_by');
    }

    public function contactPersons()
    {
        return $this->hasMany(CustomerContactPerson::class, 'customer_id');
    }

    public function primaryContact()
    {
        return $this->hasOne(CustomerContactPerson::class, 'customer_id')->where('is_primary', true);
    }

    public function orders()
    {
        return $this->hasMany(\App\Models\ProductOrder::class, 'customer_id');
    }

    public function quotations()
    {
        return $this->hasMany(\App\Models\ProductOrderQuotation::class, 'customer_id');
    }

    public function openingBalances()
    {
        return $this->hasMany(CustomerOpeningBalance::class, 'customer_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function tags()
    {
        return $this->belongsToMany(CustomerTag::class, 'crm_customer_tag_pivots', 'customer_id', 'crm_customer_tag_id')
            ->withPivot(['created_by'])
            ->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(CrmTask::class, 'customer_id');
    }

    public function activities()
    {
        return $this->hasMany(CrmActivity::class, 'customer_id');
    }

    public function notes()
    {
        return $this->hasMany(CrmNote::class, 'customer_id');
    }

    public function communications()
    {
        return $this->hasMany(CrmCommunication::class, 'customer_id');
    }

    public function leads()
    {
        return $this->hasMany(CrmLead::class, 'customer_id');
    }

    public function payments()
    {
        return $this->hasMany(DbCustomerPayment::class, 'customer_id');
    }

    public function contactHistories()
    {
        return $this->hasMany(CustomerContactHistory::class, 'customer_id');
    }

    public function nextContactDates()
    {
        return $this->hasMany(CustomerNextContactDate::class, 'customer_id');
    }

    public function returns()
    {
        return $this->hasMany(ProductOrderReturn::class, 'customer_id');
    }

    public function refunds()
    {
        return $this->hasMany(ProductOrderRefund::class, 'customer_id');
    }

    public function billingAddresses()
    {
        return $this->hasMany(BillingAddress::class, 'customer_id');
    }

    public function shippingAddresses()
    {
        return $this->hasMany(ShippingInfo::class, 'customer_id');
    }

}
