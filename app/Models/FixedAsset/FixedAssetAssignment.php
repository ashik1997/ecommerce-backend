<?php

namespace App\Models\FixedAsset;

use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use App\Models\Hrat\Department;
use App\Models\Hrat\EmployeeProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetAssignment extends Model
{
    use HasFactory;

    protected $table = 'fa_assignments';
    protected $guarded = [];

    protected $casts = [
        'assigned_at' => 'date',
        'expected_return_date' => 'date',
        'returned_at' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(ProductWarehouse::class, 'warehouse_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function employee()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWarehouse($query, $warehouseId)
    {
        return $warehouseId ? $query->where('warehouse_id', $warehouseId) : $query;
    }
}
