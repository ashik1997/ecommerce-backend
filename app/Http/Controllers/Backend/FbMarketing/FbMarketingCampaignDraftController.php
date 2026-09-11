<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FbMarketing\FbMarketingCampaignDraftRequest;
use App\Http\Requests\Backend\FbMarketing\StoreFbmCampaignOperationalActionRequest;
use App\Http\Requests\Backend\FbMarketing\StoreFbmCampaignDraftRequest;
use App\Http\Requests\Backend\FbMarketing\UpdateFbmCampaignDraftStatusRequest;
use App\Services\FbMarketing\FbmCampaignOperationalActionService;
use App\Services\FbMarketing\FbmCampaignDraftPlannerService;
use App\Services\FbMarketing\FbmCampaignPublishEngineService;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FbMarketingCampaignDraftController extends Controller
{
    public function index(
        FbMarketingCampaignDraftRequest $request,
        FbmCampaignDraftPlannerService $drafts,
        FbmCampaignPublishEngineService $publishEngine,
        FbmCampaignOperationalActionService $operationalActions,
        RoleSidebarPermissionService $permissions
    ): View {
        return view('backend.fb-marketing.campaign-drafts', [
            'report' => $drafts->build($request->validated()),
            'publishEngine' => $publishEngine->build(),
            'operationalActions' => $operationalActions->build(),
            'canManageDrafts' => $permissions->userCan($request->user(), 'fb_marketing_campaign_draft_manage', 'create'),
            'canSubmitDrafts' => $permissions->userCan($request->user(), 'fb_marketing_campaign_draft_submit', 'update'),
            'canApproveDrafts' => $permissions->userCan($request->user(), 'fb_marketing_campaign_draft_approve', 'update'),
            'canPublishDrafts' => $permissions->userCan($request->user(), 'fb_marketing_campaign_publish_create', 'create'),
            'canManageOperationalActions' => $permissions->userCan($request->user(), 'fb_marketing_campaign_operational_action_create', 'create'),
        ]);
    }

    public function store(StoreFbmCampaignDraftRequest $request, FbmCampaignDraftPlannerService $drafts): RedirectResponse
    {
        $drafts->storeDraft($request->validated(), optional($request->user())->id);

        return redirect()->route('fbMarketing.campaign-drafts.index', $request->query())->with('success', 'Campaign draft saved locally.');
    }

    public function submit(UpdateFbmCampaignDraftStatusRequest $request, int $draft, FbmCampaignDraftPlannerService $drafts): RedirectResponse
    {
        $drafts->submit($draft, optional($request->user())->id, $request->validated()['comment'] ?? null);

        return redirect()->route('fbMarketing.campaign-drafts.index', $request->query())->with('success', 'Campaign draft submitted for approval.');
    }

    public function approve(UpdateFbmCampaignDraftStatusRequest $request, int $draft, FbmCampaignDraftPlannerService $drafts): RedirectResponse
    {
        $drafts->approve($draft, optional($request->user())->id, $request->validated()['comment'] ?? null);

        return redirect()->route('fbMarketing.campaign-drafts.index', $request->query())->with('success', 'Campaign draft approved and immutable publish snapshot created.');
    }

    public function reject(UpdateFbmCampaignDraftStatusRequest $request, int $draft, FbmCampaignDraftPlannerService $drafts): RedirectResponse
    {
        $drafts->reject($draft, optional($request->user())->id, $request->validated()['comment'] ?? null);

        return redirect()->route('fbMarketing.campaign-drafts.index', $request->query())->with('success', 'Campaign draft rejected locally.');
    }

    public function publish(int $draft, FbmCampaignPublishEngineService $publishEngine): RedirectResponse
    {
        $result = $publishEngine->publish($draft, optional(request()->user())->id);
        $status = (string) ($result['status'] ?? 'skipped');
        $message = (string) ($result['message'] ?? 'Publish attempt processed.');

        return redirect()->route('fbMarketing.campaign-drafts.index', request()->query())
            ->with(in_array($status, ['blocked_preflight', 'ready_for_provider_worker', 'queued_for_provider_worker', 'running', 'completed'], true) ? 'success' : 'error', $message);
    }

    public function operationalAction(
        StoreFbmCampaignOperationalActionRequest $request,
        int $draft,
        FbmCampaignOperationalActionService $operationalActions
    ): RedirectResponse {
        $result = $operationalActions->create($draft, $request->validated(), optional($request->user())->id);
        $status = (string) ($result['status'] ?? 'skipped');
        $message = (string) ($result['message'] ?? 'Operational action processed.');

        return redirect()->route('fbMarketing.campaign-drafts.index', $request->query())
            ->with(in_array($status, ['blocked_preflight', 'ready_for_provider_worker', 'queued_for_provider_worker', 'running', 'completed'], true) ? 'success' : 'error', $message);
    }
}
