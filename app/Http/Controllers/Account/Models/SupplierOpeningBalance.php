<?php

namespace App\Http\Controllers\Account\Models;

use App\Http\Controllers\Inventory\Models\ProductSupplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierOpeningBalance extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'opening_date' => 'date',
        'opening_amount' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'remaining_amount' => 'decimal:4',
        'posted_to_accounts' => 'boolean',
    ];

    public function supplier()
    {
        return $this->belongsTo(ProductSupplier::class, 'supplier_id');
    }

    public function payments()
    {
        return $this->hasMany(DbSupplierPayment::class, 'supplier_opening_balance_id');
    }
}
