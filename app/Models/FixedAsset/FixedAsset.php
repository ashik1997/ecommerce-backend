<?php

namespace App\Models\FixedAsset;

use App\Http\Controllers\Inventory\Models\ProductSupplier;
use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use App\Models\Hrat\Department;
use App\Models\Hrat\EmployeeProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    use HasFactory;

    protected $table = 'fa_assets';
    protected $guarded = [];

    protected $casts = [
        'purchase_date' => 'date',
        'available_for_use_date' => 'date',
        'depreciation_start_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'approved_at' => 'datetime',
        'purchase_cost' => 'decimal:4',
        'additional_cost' => 'decimal:4',
        'capitalized_cost' => 'decimal:4',
        'residual_value' => 'decimal:4',
        'accumulated_depreciation' => 'decimal:4',
        'carrying_amount' => 'decimal:4',
        'useful_life_months' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(FixedAssetCategory::class, 'category_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(ProductWarehouse::class, 'warehouse_id');
    }

    public function location()
    {
        return $this->belongsTo(FixedAssetLocation::class, 'location_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function custodianEmployee()
    {
        return $this->belongsTo(EmployeeProfile::class, 'custodian_employee_id');
    }

    public function supplier()
    {
        return $this->belongsTo(ProductSupplier::class, 'supplier_id');
    }

    public function events()
    {
        return $this->hasMany(FixedAssetEvent::class, 'asset_id')->latest();
    }

    public function activeAssignment()
    {
        return $this->hasOne(FixedAssetAssignment::class, 'asset_id')->where('status', 'active')->latestOfMany();
    }

    public function assignments()
    {
        return $this->hasMany(FixedAssetAssignment::class, 'asset_id')->latest();
    }

    public function transfers()
    {
        return $this->hasMany(FixedAssetTransfer::class, 'asset_id')->latest();
    }

    public function maintenanceJobs()
    {
        return $this->hasMany(FixedAssetMaintenanceJob::class, 'asset_id')->latest();
    }

    public function depreciationEntries()
    {
        return $this->hasMany(FixedAssetDepreciationEntry::class, 'asset_id')->latest();
    }

    public function latestDepreciationEntry()
    {
        return $this->hasOne(FixedAssetDepreciationEntry::class, 'asset_id')->latestOfMany();
    }

    public function disposal()
    {
        return $this->hasOne(FixedAssetDisposal::class, 'asset_id')->latestOfMany();
    }

    public function verificationItems()
    {
        return $this->hasMany(FixedAssetVerificationItem::class, 'asset_id')->latest();
    }

    public function scopeWarehouse($query, $warehouseId)
    {
        return $warehouseId ? $query->where('warehouse_id', $warehouseId) : $query;
    }

    public function scopeCategory($query, $categoryId)
    {
        return $categoryId ? $query->where('category_id', $categoryId) : $query;
    }

    public function scopeOperationalStatus($query, $status)
    {
        return $status ? $query->where('operational_status', $status) : $query;
    }

    public function scopeActiveBook($query)
    {
        return $query->whereNotIn('lifecycle_status', ['disposed', 'archived']);
    }

    public function getBookValueAttribute()
    {
        return (float) ($this->carrying_amount ?? 0);
    }

    public function getDisplayNameAttribute()
    {
        return trim(($this->asset_code ? $this->asset_code . ' - ' : '') . $this->asset_name);
    }
}
