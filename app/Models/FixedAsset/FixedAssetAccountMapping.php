<?php

namespace App\Models\FixedAsset;

use App\Http\Controllers\Account\Models\AcAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetAccountMapping extends Model
{
    use HasFactory;

    protected $table = 'fa_account_mappings';
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(FixedAssetCategory::class, 'category_id');
    }

    public function debitAccount()
    {
        return $this->belongsTo(AcAccount::class, 'debit_account_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(AcAccount::class, 'credit_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEvent($query, $eventKey)
    {
        return $eventKey ? $query->where('event_key', $eventKey) : $query;
    }
}
