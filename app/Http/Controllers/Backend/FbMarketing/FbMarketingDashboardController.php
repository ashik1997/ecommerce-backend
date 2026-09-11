<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FbMarketing\FbMarketingDashboardFilterRequest;
use App\Models\FbMarketing\FbmAssetDiscoveryRun;
use App\Services\FbMarketing\FbmAssetDiscoveryService;
use App\Services\FbMarketing\FbmCampaignHierarchySyncService;
use App\Services\FbMarketing\FbmInsightsSnapshotService;
use App\Services\FbMarketing\FbmExecutiveDashboardService;
use App\Models\FbMarketing\FbmCampaign;
use App\Models\FbMarketing\FbmAdSet;
use App\Models\FbMarketing\FbmAd;
use App\Models\FbMarketing\FbmCreative;
use App\Models\FbMarketing\FbmSyncRunItem;
use App\Services\RoleSidebarPermissionService;

class FbMarketingDashboardController extends Controller
{
    public function index(
        FbMarketingDashboardFilterRequest $request,
        RoleSidebarPermissionService $permissionService,
        FbmInsightsSnapshotService $insightsSnapshotService,
        FbmExecutiveDashboardService $executiveDashboardService
    )
    {
        $assetDiscoveryReady = FbmAssetDiscoveryService::schemaReady();
        $assets = collect();
        $latestDiscoveryRun = null;
        $hierarchyReady = FbmCampaignHierarchySyncService::schemaReady();
        $hierarchyCounts = [];
        $latestHierarchyItems = collect();
        $insightsSummary = $insightsSnapshotService->operationalSummary();
        $executiveDashboard = $executiveDashboardService->build($request->validated());

        if ($assetDiscoveryReady) {
            foreach (FbmAssetDiscoveryService::ASSET_MODEL_MAP as $family => $modelClass) {
                $assets[$family] = $modelClass::query()
                    ->with(['connection:id,connection_name', 'businessAccount:id,asset_name'])
                    ->orderByDesc('is_selected')
                    ->orderByDesc('is_available')
                    ->orderBy('asset_name')
                    ->get()
                    ->map(fn($asset): array => $asset->toSafeSummary());
            }

            $latest = FbmAssetDiscoveryRun::query()
                ->with('connection:id,connection_name')
                ->orderByDesc('completed_at')
                ->orderByDesc('id')
                ->first();
            $latestDiscoveryRun = $latest ? $latest->toSafeSummary() : null;
        }

        if ($hierarchyReady) {
            $hierarchyCounts = [
                'campaigns' => FbmCampaign::query()->where('is_available', true)->count(),
                'ad_sets' => FbmAdSet::query()->where('is_available', true)->count(),
                'ads' => FbmAd::query()->where('is_available', true)->count(),
                'creatives' => FbmCreative::query()->where('is_available', true)->count(),
            ];
            $latestHierarchyItems = FbmSyncRunItem::query()->with(['connection:id,connection_name','adAccount:id,asset_name'])->orderByDesc('created_at')->orderByDesc('id')->limit(8)->get()->map(fn($item): array => $item->toSafeSummary());
        }

        return view('backend.fb-marketing.dashboard', [
            'assetDiscoveryReady' => $assetDiscoveryReady,
            'hierarchyReady' => $hierarchyReady,
            'hierarchyCounts' => $hierarchyCounts,
            'latestHierarchyItems' => $latestHierarchyItems,
            'insightsSummary' => $insightsSummary,
            'executiveDashboard' => $executiveDashboard,
            'assets' => $assets,
            'latestDiscoveryRun' => $latestDiscoveryRun,
            'canViewPerformance' => $permissionService->userCan($request->user(), 'fb_marketing_performance_view', 'read'),
            'canManageAssetSelection' => $permissionService->userCan($request->user(), 'fb_marketing_asset_selection_manage', 'update'),
            'assetLabels' => [
                'business_accounts' => 'Business accounts',
                'ad_accounts' => 'Ad accounts',
                'pages' => 'Pages',
                'pixels' => 'Pixels',
                'datasets' => 'Datasets',
                'catalogs' => 'Catalogs',
                'instagram_accounts' => 'Instagram accounts',
            ],
        ]);
    }
}
