<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FbMarketing\FbMarketingPerformanceFilterRequest;
use App\Models\FbMarketing\FbmAd;
use App\Models\FbMarketing\FbmAdSet;
use App\Models\FbMarketing\FbmCampaign;
use App\Services\FbMarketing\FbmPerformanceDrilldownService;
use App\Services\FbMarketing\FbmPerformanceHealthService;
use App\Services\FbMarketing\FbmPerformanceWorklistService;
use App\Services\RoleSidebarPermissionService;
use Illuminate\View\View;

class FbMarketingPerformanceController extends Controller
{
    public function index(
        FbMarketingPerformanceFilterRequest $request,
        FbmPerformanceDrilldownService $drilldowns,
        FbmPerformanceWorklistService $worklists,
        FbmPerformanceHealthService $health,
        RoleSidebarPermissionService $permissions
    ): View {
        $performance = $drilldowns->overview($request->validated());

        return view('backend.fb-marketing.performance.index', [
            'performance' => $performance,
            'worklist' => $worklists->forRows($performance['rows'], 'campaign'),
            'health' => $health->summary(),
            'canViewConfiguration' => $permissions->userCan($request->user(), 'fb_marketing_configuration_view', 'read'),
        ]);
    }

    public function campaign(
        FbMarketingPerformanceFilterRequest $request,
        int $campaign,
        FbmPerformanceDrilldownService $drilldowns,
        FbmPerformanceWorklistService $worklists,
        FbmPerformanceHealthService $health
    ): View {
        $performance = $drilldowns->campaign(FbmCampaign::query()->findOrFail($campaign), $request->validated());

        return view('backend.fb-marketing.performance.campaign', [
            'performance' => $performance,
            'worklist' => $worklists->forRows($performance['child_rows'], 'adset'),
            'health' => $health->summary(),
        ]);
    }

    public function adSet(
        FbMarketingPerformanceFilterRequest $request,
        int $adSet,
        FbmPerformanceDrilldownService $drilldowns,
        FbmPerformanceWorklistService $worklists,
        FbmPerformanceHealthService $health
    ): View {
        $performance = $drilldowns->adSet(FbmAdSet::query()->findOrFail($adSet), $request->validated());

        return view('backend.fb-marketing.performance.ad-set', [
            'performance' => $performance,
            'worklist' => $worklists->forRows($performance['child_rows'], 'ad'),
            'health' => $health->summary(),
        ]);
    }

    public function ad(
        FbMarketingPerformanceFilterRequest $request,
        int $ad,
        FbmPerformanceDrilldownService $drilldowns,
        FbmPerformanceHealthService $health
    ): View {
        return view('backend.fb-marketing.performance.ad', [
            'performance' => $drilldowns->ad(FbmAd::query()->findOrFail($ad), $request->validated()),
            'health' => $health->summary(),
        ]);
    }
}
