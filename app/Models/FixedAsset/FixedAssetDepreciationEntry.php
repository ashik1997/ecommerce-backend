<?php

namespace App\Models\FixedAsset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetDepreciationEntry extends Model
{
    use HasFactory;

    protected $table = 'fa_depreciation_entries';
    protected $guarded = [];

    protected $casts = [
        'opening_book_value' => 'decimal:4',
        'depreciation_amount' => 'decimal:4',
        'closing_book_value' => 'decimal:4',
    ];

    public function run()
    {
        return $this->belongsTo(FixedAssetDepreciationRun::class, 'depreciation_run_id');
    }

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }

    public function scopePeriod($query, $period)
    {
        return $period ? $query->where('period', $period) : $query;
    }
}
