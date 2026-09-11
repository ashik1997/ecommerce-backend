<?php

namespace App\Models\FixedAsset;

use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetMaintenanceJob extends Model
{
    use HasFactory;

    protected $table = 'fa_maintenance_jobs';
    protected $guarded = [];

    protected $casts = [
        'reported_at' => 'date',
        'started_at' => 'date',
        'completed_at' => 'date',
        'parts_cost' => 'decimal:4',
        'labor_cost' => 'decimal:4',
        'other_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(ProductWarehouse::class, 'warehouse_id');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    public function scopeWarehouse($query, $warehouseId)
    {
        return $warehouseId ? $query->where('warehouse_id', $warehouseId) : $query;
    }
}
