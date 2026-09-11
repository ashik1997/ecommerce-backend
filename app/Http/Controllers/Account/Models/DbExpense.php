<?php

namespace App\Http\Controllers\Account\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DbExpense extends Model
{
    use HasFactory;
    protected $guarded = []; 

    protected $table = "db_expenses";

    public function user() {
        return $this->belongsTo(User::class, 'creator');
    }

    public function expense_category() {
        return $this->belongsTo(DbExpenseCategory::class, 'category_id');
    }

    public function category() {
        return $this->belongsTo(DbExpenseCategory::class, 'category_id');
    }

    public function payments() {
        return $this->hasMany(DbExpensePayment::class, 'expense_id');
    }

    public function payment_type() {
        return $this->belongsTo(DbPaymentType::class, 'payment_type_id');
    }
    
    public function account() {
        return $this->belongsTo(AcAccount::class, 'account_id');
    }

    public function debitAccount() {
        return $this->belongsTo(AcAccount::class, 'debit_account_id');
    }

    public function creditAccount() {
        return $this->belongsTo(AcAccount::class, 'credit_account_id');
    }
    
}
