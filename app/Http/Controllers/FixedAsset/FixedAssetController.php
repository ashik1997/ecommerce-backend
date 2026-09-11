<?php

namespace App\Http\Controllers\FixedAsset;

use App\Http\Controllers\Controller;
use App\Http\Requests\FixedAsset\FixedAssetRequest;
use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetCategory;
use App\Models\FixedAsset\FixedAssetLocation;
use App\Services\FixedAsset\FixedAssetLifecycleService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FixedAssetController extends Controller
{
    public function index(Request $request)
    {
        $query = FixedAsset::with(['category', 'warehouse', 'location'])->latest();

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('status')) {
            $query->where('operational_status', $request->status);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn ($s) => $s->where('asset_code', 'like', "%{$q}%")
                ->orWhere('asset_name', 'like', "%{$q}%")
                ->orWhere('serial_number', 'like', "%{$q}%"));
        }

        return view('backend.fixed_asset.assets.index', [
            'assets' => $query->paginate(30)->withQueryString(),
            'categories' => FixedAssetCategory::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => DB::table('product_warehouses')->where('status', 'active')->orderBy('title')->get(),
        ]);
    }

    public function create()
    {
        return view('backend.fixed_asset.assets.create', $this->formData());
    }

    public function store(FixedAssetRequest $request, FixedAssetLifecycleService $service)
    {
        $asset = $service->create($request->validated());
        Toastr::success('Fixed asset created and accounting posted successfully.', 'Success');
        return redirect()->route('fixed-assets.assets.show', $asset);
    }

    public function show(FixedAsset $asset)
    {
        $asset->load(['category', 'warehouse', 'location', 'events', 'activeAssignment']);
        return view('backend.fixed_asset.assets.show', compact('asset'));
    }

    public function edit(FixedAsset $asset)
    {
        return view('backend.fixed_asset.assets.edit', $this->formData() + compact('asset'));
    }

    public function update(FixedAssetRequest $request, FixedAsset $asset, FixedAssetLifecycleService $service)
    {
        $asset = $service->update($asset, $request->validated());
        Toastr::success('Fixed asset updated successfully.', 'Success');
        return redirect()->route('fixed-assets.assets.show', $asset);
    }

    public function archive(FixedAsset $asset, FixedAssetLifecycleService $service)
    {
        if ($asset->lifecycle_status === 'disposed') {
            Toastr::warning('Disposed asset is already closed.', 'Warning');
            return back();
        }

        $service->archive($asset);
        Toastr::success('Asset archived.', 'Success');
        return redirect()->route('fixed-assets.assets.index');
    }

    public function tag(FixedAsset $asset)
    {
        return view('backend.fixed_asset.assets.tag', compact('asset'));
    }

    private function formData(): array
    {
        return [
            'categories' => FixedAssetCategory::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => DB::table('product_warehouses')->where('status', 'active')->orderBy('title')->get(),
            'locations' => FixedAssetLocation::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
