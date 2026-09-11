<?php

namespace App\Models\FixedAsset;

use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetDisposal extends Model
{
    use HasFactory;

    protected $table = 'fa_disposals';
    protected $guarded = [];

    protected $casts = [
        'disposal_date' => 'date',
        'approved_at' => 'datetime',
        'carrying_amount' => 'decimal:4',
        'proceeds_amount' => 'decimal:4',
        'gain_loss_amount' => 'decimal:4',
    ];

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(ProductWarehouse::class, 'warehouse_id');
    }

    public function reason()
    {
        return $this->belongsTo(FixedAssetDisposalReason::class, 'disposal_reason_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeWarehouse($query, $warehouseId)
    {
        return $warehouseId ? $query->where('warehouse_id', $warehouseId) : $query;
    }
}
