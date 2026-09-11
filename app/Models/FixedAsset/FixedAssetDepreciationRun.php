<?php

namespace App\Models\FixedAsset;

use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetDepreciationRun extends Model
{
    use HasFactory;

    protected $table = 'fa_depreciation_runs';
    protected $guarded = [];

    protected $casts = [
        'run_date' => 'date',
        'posted_at' => 'datetime',
        'total_depreciation' => 'decimal:4',
    ];

    public function warehouse()
    {
        return $this->belongsTo(ProductWarehouse::class, 'warehouse_id');
    }

    public function entries()
    {
        return $this->hasMany(FixedAssetDepreciationEntry::class, 'depreciation_run_id');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopePeriod($query, $period)
    {
        return $period ? $query->where('period', $period) : $query;
    }

    public function scopeWarehouse($query, $warehouseId)
    {
        return $warehouseId ? $query->where('warehouse_id', $warehouseId) : $query;
    }
}
