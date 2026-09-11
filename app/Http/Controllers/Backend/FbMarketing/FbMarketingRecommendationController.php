<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Services\FbMarketing\FbmRecommendationRuleService;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FbMarketingRecommendationController extends Controller
{
    public function index(Request $request, FbmRecommendationRuleService $service, RoleSidebarPermissionService $permissions): View
    {
        return view('backend.fb-marketing.recommendations', [
            'report' => $service->build(),
            'canRefresh' => $permissions->userCan($request->user(), 'fb_marketing_recommendation_refresh', 'update'),
            'canDecide' => $permissions->userCan($request->user(), 'fb_marketing_recommendation_decide', 'update'),
        ]);
    }

    public function refresh(Request $request, FbmRecommendationRuleService $service): RedirectResponse
    {
        $result = $service->refresh(optional($request->user())->id);

        return redirect()->route('fbMarketing.recommendations.index')
            ->with($result['status'] === 'completed' ? 'success' : 'error', $result['message'] . ' ' . (int) ($result['count'] ?? 0) . ' recommendation(s).');
    }

    public function approve(Request $request, string $uuid, FbmRecommendationRuleService $service): RedirectResponse
    {
        $data = $request->validate(['decision_note' => ['nullable', 'string', 'max:500']]);
        $result = $service->approve($uuid, optional($request->user())->id, $data['decision_note'] ?? null);

        return redirect()->route('fbMarketing.recommendations.index')
            ->with($result['status'] === 'approved' ? 'success' : 'error', $result['message']);
    }

    public function dismiss(Request $request, string $uuid, FbmRecommendationRuleService $service): RedirectResponse
    {
        $data = $request->validate(['decision_note' => ['nullable', 'string', 'max:500']]);
        $result = $service->dismiss($uuid, optional($request->user())->id, $data['decision_note'] ?? null);

        return redirect()->route('fbMarketing.recommendations.index')
            ->with($result['status'] === 'dismissed' ? 'success' : 'error', $result['message']);
    }
}
