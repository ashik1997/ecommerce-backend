<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FbMarketing\FbMarketingCreativeLibraryRequest;
use App\Http\Requests\Backend\FbMarketing\StoreFbmCreativeAssetRequest;
use App\Services\FbMarketing\FbmCreativeLibraryService;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FbMarketingCreativeLibraryController extends Controller
{
    public function index(
        FbMarketingCreativeLibraryRequest $request,
        FbmCreativeLibraryService $library,
        RoleSidebarPermissionService $permissions
    ): View {
        return view('backend.fb-marketing.creative-library', [
            'report' => $library->build($request->validated()),
            'canManageAssets' => $permissions->userCan($request->user(), 'fb_marketing_creative_asset_manage', 'create'),
            'canRunPreflight' => $permissions->userCan($request->user(), 'fb_marketing_creative_preflight_run', 'create'),
        ]);
    }

    public function store(StoreFbmCreativeAssetRequest $request, FbmCreativeLibraryService $library): RedirectResponse
    {
        $library->storeAsset($request->validated(), optional($request->user())->id);

        return redirect()->route('fbMarketing.creative-library.index', $request->query())->with('success', 'Creative asset saved locally.');
    }

    public function preflight(int $asset, FbmCreativeLibraryService $library): RedirectResponse
    {
        $library->runPreflight($asset, optional(request()->user())->id);

        return redirect()->route('fbMarketing.creative-library.index', request()->query())->with('success', 'Creative preflight completed.');
    }
}
