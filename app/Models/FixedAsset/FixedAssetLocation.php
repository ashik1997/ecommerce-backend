<?php

namespace App\Models\FixedAsset;

use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetLocation extends Model
{
    use HasFactory;

    protected $table = 'fa_locations';
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function warehouse()
    {
        return $this->belongsTo(ProductWarehouse::class, 'warehouse_id');
    }

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
        return $this->hasMany(FixedAsset::class, 'location_id');
    }

    public function scopeWarehouse($query, $warehouseId)
    {
        return $warehouseId ? $query->where('warehouse_id', $warehouseId) : $query;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDisplayNameAttribute()
    {
        return trim(($this->code ? $this->code . ' - ' : '') . $this->name);
    }
}
