<?php

namespace App\Http\Controllers\FixedAsset;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetMaintenanceJob;
use App\Models\FixedAsset\FixedAssetDisposal;
use Illuminate\Support\Facades\DB;

class FixedAssetDashboardController extends Controller
{
    public function index()
    {
        $summary = [
            'total_assets' => FixedAsset::count(),
            'capitalized_cost' => FixedAsset::sum('capitalized_cost'),
            'book_value' => FixedAsset::sum('carrying_amount'),
            'assigned_assets' => FixedAsset::where('operational_status', 'assigned')->count(),
            'available_assets' => FixedAsset::where('operational_status', 'available')->count(),
            'under_maintenance' => FixedAsset::where('operational_status', 'under_maintenance')->count(),
            'disposed_assets' => FixedAsset::where('lifecycle_status', 'disposed')->count(),
            'open_maintenance' => FixedAssetMaintenanceJob::whereIn('status', ['open','in_progress'])->count(),
        ];

        $warehouseSummaries = FixedAsset::query()
            ->leftJoin('product_warehouses', 'fa_assets.warehouse_id', '=', 'product_warehouses.id')
            ->select('product_warehouses.title as warehouse_name', DB::raw('COUNT(fa_assets.id) as total_assets'), DB::raw('SUM(fa_assets.capitalized_cost) as total_cost'), DB::raw('SUM(fa_assets.carrying_amount) as book_value'))
            ->groupBy('product_warehouses.title')
            ->orderBy('product_warehouses.title')
            ->get();

        $recentAssets = FixedAsset::with(['category','warehouse'])->latest()->limit(10)->get();
        $recentDisposals = FixedAssetDisposal::latest()->limit(5)->get();

        return view('backend.fixed_asset.dashboard', compact('summary','warehouseSummaries','recentAssets','recentDisposals'));
    }
}
