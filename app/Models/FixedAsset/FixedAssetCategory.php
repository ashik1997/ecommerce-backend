<?php

namespace App\Models\FixedAsset;

use App\Http\Controllers\Account\Models\AcAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetCategory extends Model
{
    use HasFactory;

    protected $table = 'fa_categories';
    protected $guarded = [];

    protected $casts = [
        'is_depreciable' => 'boolean',
        'is_active' => 'boolean',
        'default_useful_life_months' => 'integer',
        'default_residual_value' => 'decimal:4',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function assets()
    {
        return $this->hasMany(FixedAsset::class, 'category_id');
    }

    public function activeAssets()
    {
        return $this->hasMany(FixedAsset::class, 'category_id')->whereNotIn('lifecycle_status', ['disposed', 'archived']);
    }

    public function assetAccount()
    {
        return $this->belongsTo(AcAccount::class, 'asset_account_id');
    }

    public function accumulatedDepreciationAccount()
    {
        return $this->belongsTo(AcAccount::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount()
    {
        return $this->belongsTo(AcAccount::class, 'depreciation_expense_account_id');
    }

    public function accountMappings()
    {
        return $this->hasMany(FixedAssetAccountMapping::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
