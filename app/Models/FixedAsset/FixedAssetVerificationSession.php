<?php

namespace App\Models\FixedAsset;

use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetVerificationSession extends Model
{
    use HasFactory;

    protected $table = 'fa_verification_sessions';
    protected $guarded = [];

    protected $casts = [
        'started_at' => 'date',
        'completed_at' => 'date',
    ];

    public function warehouse()
    {
        return $this->belongsTo(ProductWarehouse::class, 'warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(FixedAssetVerificationItem::class, 'verification_session_id');
    }

    public function foundItems()
    {
        return $this->hasMany(FixedAssetVerificationItem::class, 'verification_session_id')->where('result', 'found');
    }

    public function missingItems()
    {
        return $this->hasMany(FixedAssetVerificationItem::class, 'verification_session_id')->where('result', 'missing');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeWarehouse($query, $warehouseId)
    {
        return $warehouseId ? $query->where('warehouse_id', $warehouseId) : $query;
    }
}
