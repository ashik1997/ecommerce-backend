<?php

namespace App\Models\FixedAsset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetDepreciationProfile extends Model
{
    use HasFactory;

    protected $table = 'fa_depreciation_profiles';
    protected $guarded = [];

    protected $casts = [
        'useful_life_months' => 'integer',
        'residual_value' => 'decimal:4',
        'rate_percent' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
