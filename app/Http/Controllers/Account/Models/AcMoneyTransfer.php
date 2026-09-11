<?php

namespace App\Http\Controllers\Account\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcMoneyTransfer extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'ac_moneytransfer';

    public function fromPaymentType()
    {
        return $this->belongsTo(DbPaymentType::class, 'from_payment_type_id');
    }

    public function toPaymentType()
    {
        return $this->belongsTo(DbPaymentType::class, 'to_payment_type_id');
    }

    public function transferType()
    {
        return $this->belongsTo(AcMoneyTransferType::class, 'transfer_type_id');
    }

    public function debitAccount()
    {
        return $this->belongsTo(AcAccount::class, 'debit_account_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(AcAccount::class, 'credit_account_id');
    }

    public function chargeAccount()
    {
        return $this->belongsTo(AcAccount::class, 'charge_account_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator_info()
    {
        return $this->belongsTo(User::class, 'creator');
    }
}
