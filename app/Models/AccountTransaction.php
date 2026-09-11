<?php

namespace App\Models;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;

/**
 * Compatibility alias for older project code that imports App\Models\AccountTransaction.
 * New accounting code should prefer App\Http\Controllers\Account\Models\AcTransaction.
 */
class AccountTransaction extends AcTransaction
{
    protected $table = 'ac_transactions';

    public function debitAccount()
    {
        return $this->belongsTo(AcAccount::class, 'debit_account_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(AcAccount::class, 'credit_account_id');
    }
}
