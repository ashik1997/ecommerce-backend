<?php

namespace App\Http\Controllers\FixedAsset;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset\FixedAssetCategory;
use App\Models\FixedAsset\FixedAssetDepreciationProfile;
use App\Models\FixedAsset\FixedAssetDisposalReason;
use App\Models\FixedAsset\FixedAssetLocation;
use App\Services\FixedAsset\FixedAssetAccountHeadService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FixedAssetSettingController extends Controller
{
    public function index(FixedAssetAccountHeadService $accountHeadService)
    {
        return view('backend.fixed_asset.settings.index', [
            'categories' => FixedAssetCategory::orderBy('name')->get(),
            'locations' => FixedAssetLocation::with([])->latest()->get(),
            'profiles' => FixedAssetDepreciationProfile::orderBy('name')->get(),
            'reasons' => FixedAssetDisposalReason::orderBy('name')->get(),
            'warehouses' => DB::table('product_warehouses')->where('status','active')->orderBy('title')->get(),
            'installStatus' => $accountHeadService->installStatus(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'code' => ['required','string','max:30','unique:fa_categories,code'],
            'tracking_mode' => ['required','in:individual,pooled'],
            'is_depreciable' => ['nullable','boolean'],
            'default_useful_life_months' => ['nullable','integer','min:1'],
            'default_residual_value' => ['nullable','numeric','min:0'],
        ]);
        $data['is_depreciable'] = $request->boolean('is_depreciable');
        $data['created_by'] = auth()->id();
        FixedAssetCategory::create($data);
        Toastr::success('Asset category saved.', 'Success');
        return back();
    }

    public function storeLocation(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required','exists:product_warehouses,id'],
            'name' => ['required','string','max:255'],
            'code' => ['nullable','string','max:50'],
            'type' => ['required','in:building,floor,room,zone,rack,desk,other'],
            'description' => ['nullable','string'],
        ]);
        $data['created_by'] = auth()->id();
        FixedAssetLocation::create($data);
        Toastr::success('Asset location saved.', 'Success');
        return back();
    }

    public function storeProfile(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'method' => ['required','in:straight_line,diminishing_balance,units_of_production,none'],
            'useful_life_months' => ['nullable','integer','min:1'],
            'residual_value' => ['nullable','numeric','min:0'],
        ]);
        $data['created_by'] = auth()->id();
        FixedAssetDepreciationProfile::create($data);
        Toastr::success('Depreciation profile saved.', 'Success');
        return back();
    }

    public function ensureAccounts(FixedAssetAccountHeadService $service)
    {
        $status = $service->installStatus();

        if ($status['installed']) {
            Toastr::info('Fixed Asset module is already installed. No account heads were changed.', 'Already Installed');
            return back();
        }

        $service->ensureRequiredHeads();
        $afterStatus = $service->installStatus();

        if ($afterStatus['installed']) {
            Toastr::success('Fixed Asset module installed successfully. Missing account heads have been created.', 'Installed');
        } else {
            Toastr::warning('Install attempted, but some account heads are still missing. Please check chart of accounts.', 'Check Required');
        }

        return back();
    }
}
