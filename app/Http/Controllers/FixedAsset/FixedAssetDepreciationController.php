<?php

namespace App\Http\Controllers\FixedAsset;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset\FixedAssetDepreciationRun;
use App\Services\FixedAsset\DepreciationService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FixedAssetDepreciationController extends Controller
{
    public function index()
    {
        return view('backend.fixed_asset.depreciation.index', [
            'runs' => FixedAssetDepreciationRun::latest()->paginate(30),
            'warehouses' => DB::table('product_warehouses')->where('status','active')->orderBy('title')->get(),
        ]);
    }

    public function preview(Request $request, DepreciationService $service)
    {
        $data = $request->validate([
            'period' => ['required','date_format:Y-m'],
            'warehouse_id' => ['nullable','exists:product_warehouses,id'],
        ]);

        $preview = $service->preview($data['period'], $data['warehouse_id'] ?? null);

        return view('backend.fixed_asset.depreciation.preview', [
            'preview' => $preview,
            'period' => $data['period'],
            'warehouseId' => $data['warehouse_id'] ?? null,
        ]);
    }

    public function post(Request $request, DepreciationService $service)
    {
        $data = $request->validate([
            'period' => ['required','date_format:Y-m'],
            'warehouse_id' => ['nullable','exists:product_warehouses,id'],
        ]);

        try {
            $service->post($data['period'], $data['warehouse_id'] ?? null);
            Toastr::success('Depreciation posted successfully.', 'Success');
        } catch (RuntimeException $exception) {
            Toastr::error($exception->getMessage(), 'Error');
        }

        return redirect()->route('fixed-assets.depreciation.index');
    }

    public function reverse(FixedAssetDepreciationRun $run, DepreciationService $service)
    {
        try {
            $service->reverse($run);
            Toastr::success('Depreciation run reversed successfully.', 'Success');
        } catch (RuntimeException $exception) {
            Toastr::error($exception->getMessage(), 'Error');
        }

        return back();
    }
}
