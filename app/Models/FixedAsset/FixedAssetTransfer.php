<?php

namespace App\Models\FixedAsset;

use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetTransfer extends Model
{
    use HasFactory;

    protected $table = 'fa_transfers';
    protected $guarded = [];

    protected $casts = [
        'transfer_date' => 'date',
        'received_date' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(ProductWarehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(ProductWarehouse::class, 'to_warehouse_id');
    }

    public function fromLocation()
    {
        return $this->belongsTo(FixedAssetLocation::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(FixedAssetLocation::class, 'to_location_id');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'dispatched']);
    }
}
