<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FbMarketing\FbMarketingProfitabilityFilterRequest;
use App\Http\Requests\Backend\FbMarketing\StoreFbmCampaignCostAdjustmentRequest;
use App\Services\FbMarketing\FbmProfitabilityReportService;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FbMarketingProfitabilityController extends Controller
{
    public function index(
        FbMarketingProfitabilityFilterRequest $request,
        FbmProfitabilityReportService $reports,
        RoleSidebarPermissionService $permissions
    ): View {
        return view('backend.fb-marketing.profitability', [
            'report' => $reports->build($request->validated()),
            'canManageAdjustments' => $permissions->userCan($request->user(), 'fb_marketing_profitability_adjustment_manage', 'create'),
        ]);
    }

    public function storeAdjustment(
        StoreFbmCampaignCostAdjustmentRequest $request,
        FbmProfitabilityReportService $reports
    ): RedirectResponse {
        $reports->storeAdjustment($request->validated(), optional($request->user())->id);

        return redirect()
            ->route('fbMarketing.profitability.index', $request->query())
            ->with('success', 'Local campaign cost adjustment saved safely.');
    }
}
