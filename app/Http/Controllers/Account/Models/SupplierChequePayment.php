<?php

namespace App\Http\Controllers\Account\Models;

use App\Http\Controllers\Inventory\Models\ProductPurchaseOrder;
use App\Http\Controllers\Inventory\Models\ProductSupplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SupplierChequePayment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'issue_date' => 'date',
        'execution_date' => 'date',
        'cleared_date' => 'date',
    ];

    protected $appends = ['days_left', 'cheque_state'];

    public function supplier()
    {
        return $this->belongsTo(ProductSupplier::class, 'supplier_id');
    }

    public function purchase()
    {
        return $this->belongsTo(ProductPurchaseOrder::class, 'purchase_id');
    }

    public function paymentType()
    {
        return $this->belongsTo(DbPaymentType::class, 'payment_type_id');
    }

    public function sourceAccount()
    {
        return $this->belongsTo(AcAccount::class, 'source_account_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getDaysLeftAttribute()
    {
        if (!$this->execution_date) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->execution_date->startOfDay(), false);
    }

    public function getChequeStateAttribute()
    {
        if ($this->status !== 'pending') {
            return $this->status;
        }

        $daysLeft = $this->days_left;

        if ($daysLeft < 0) {
            return 'overdue';
        }

        if ($daysLeft === 0) {
            return 'due_today';
        }

        if ($daysLeft <= 7) {
            return 'upcoming';
        }

        return 'pending';
    }
}
