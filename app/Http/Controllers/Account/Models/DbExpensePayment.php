<?php

namespace App\Http\Controllers\Account\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DbExpensePayment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'db_expense_payments';

    public function expense()
    {
        return $this->belongsTo(DbExpense::class, 'expense_id');
    }

    public function payment_type()
    {
        return $this->belongsTo(DbPaymentType::class, 'payment_type_id');
    }

    public function account()
    {
        return $this->belongsTo(AcAccount::class, 'account_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'creator');
    }
}
