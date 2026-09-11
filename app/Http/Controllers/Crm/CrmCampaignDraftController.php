<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\ApproveCrmCampaignDraftRequest;
use App\Http\Requests\Crm\ArchiveCrmCampaignDraftRequest;
use App\Http\Requests\Crm\CrmCampaignDraftWorklistRequest;
use App\Http\Requests\Crm\RejectCrmCampaignDraftRequest;
use App\Http\Requests\Crm\RefreshCrmCampaignDraftAudienceSnapshotRequest;
use App\Http\Requests\Crm\ReturnCrmCampaignDraftToDraftRequest;
use App\Http\Requests\Crm\StoreCrmCampaignDraftRequest;
use App\Http\Requests\Crm\SubmitCrmCampaignDraftForReviewRequest;
use App\Http\Requests\Crm\UpdateCrmCampaignDraftRequest;
use App\Models\Crm\CrmCampaignDraft;
use App\Services\Crm\CrmCampaignDraftService;
use App\Services\RoleSidebarPermissionService;
use DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmCampaignDraftController extends Controller
{
    public function __construct(
        protected CrmCampaignDraftService $campaignDraftService,
        protected RoleSidebarPermissionService $permissionService
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $permissions = [
            'can_create' => $this->permissionService->userCan($user, 'crm.campaign-drafts.list', 'create'),
            'can_update' => $this->permissionService->userCan($user, 'crm.campaign-drafts.list', 'update'),
            'can_archive' => $this->permissionService->userCan($user, 'crm.campaign-drafts.list', 'delete'),
            'can_use_saved_segments' => $this->permissionService->userCan($user, 'crm.saved-customer-segments.list', 'read'),
            'can_approve' => $this->permissionService->userCan($user, 'crm.campaign-drafts.approve', 'update'),
            'can_prepare_dispatch_read' => $this->permissionService->userCan($user, 'crm.campaign-drafts.prepare-dispatch', 'read'),
            'can_prepare_dispatch_create' => $this->permissionService->userCan($user, 'crm.campaign-drafts.prepare-dispatch', 'create'),
            'can_prepare_dispatch_update' => $this->permissionService->userCan($user, 'crm.campaign-drafts.prepare-dispatch', 'update'),
            'can_release_dispatch_read' => $this->permissionService->userCan($user, 'crm.campaign-drafts.release-dispatch', 'read'),
            'can_release_dispatch_create' => $this->permissionService->userCan($user, 'crm.campaign-drafts.release-dispatch', 'create'),
            'can_release_dispatch_update' => $this->permissionService->userCan($user, 'crm.campaign-drafts.release-dispatch', 'update'),
            'can_claim_dispatch_execution_read' => $this->permissionService->userCan($user, 'crm.campaign-drafts.claim-dispatch-execution', 'read'),
            'can_claim_dispatch_execution_create' => $this->permissionService->userCan($user, 'crm.campaign-drafts.claim-dispatch-execution', 'create'),
            'can_claim_dispatch_execution_update' => $this->permissionService->userCan($user, 'crm.campaign-drafts.claim-dispatch-execution', 'update'),
            'can_prepare_dispatch_attempt_read' => $this->permissionService->userCan($user, 'crm.campaign-drafts.prepare-dispatch-attempt', 'read'),
            'can_prepare_dispatch_attempt_create' => $this->permissionService->userCan($user, 'crm.campaign-drafts.prepare-dispatch-attempt', 'create'),
            'can_prepare_dispatch_attempt_update' => $this->permissionService->userCan($user, 'crm.campaign-drafts.prepare-dispatch-attempt', 'update'),
            'can_execute_dispatch_create' => $this->permissionService->userCan($user, 'crm.campaign-drafts.execute-dispatch', 'create'),
        ];

        return view('backend.crm.campaign-drafts.index', compact('permissions'));
    }

    public function data(CrmCampaignDraftWorklistRequest $request)
    {
        $query = $this->campaignDraftService->worklistQuery($request->user(), $request->validated());

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                $this->campaignDraftService->applyGlobalSearch($query, $request->input('search.value'));
            })
            ->addColumn('draft', fn (CrmCampaignDraft $draft) => $this->campaignDraftService->payload($draft, $request->user()))
            ->make(true);
    }

    public function savedSegmentOptions(Request $request): JsonResponse
    {
        return response()->json([
            'results' => $this->campaignDraftService->savedSegmentOptions($request->user(), $request->query('q'))->values(),
        ]);
    }

    public function previewSavedSegment(Request $request, int $segment): JsonResponse
    {
        return response()->json([
            'preview' => $this->campaignDraftService->previewSavedSegment($segment, $request->user()),
        ]);
    }

    public function show(Request $request, int $draft): JsonResponse
    {
        $campaignDraft = $this->campaignDraftService->findVisible($draft, $request->user());

        return response()->json([
            'draft' => $this->campaignDraftService->payload($campaignDraft, $request->user(), true),
        ]);
    }

    public function preview(Request $request, int $draft): JsonResponse
    {
        return response()->json([
            'preview' => $this->campaignDraftService->previewDraft($draft, $request->user()),
        ]);
    }


    public function preflight(Request $request, int $draft): JsonResponse
    {
        return response()->json([
            'preflight' => $this->campaignDraftService->preflight($draft, $request->user()),
        ]);
    }

    public function approvalHistory(Request $request, int $draft): JsonResponse
    {
        return response()->json([
            'history' => $this->campaignDraftService->approvalHistory($draft, $request->user()),
        ]);
    }

    public function submitForReview(SubmitCrmCampaignDraftForReviewRequest $request, int $draft): JsonResponse
    {
        $campaignDraft = $this->campaignDraftService->submitForReview($draft, $request->user());

        return response()->json([
            'message' => 'CRM campaign draft submitted for review successfully.',
            'draft' => $this->campaignDraftService->payload($campaignDraft, $request->user(), true),
        ]);
    }

    public function approve(ApproveCrmCampaignDraftRequest $request, int $draft): JsonResponse
    {
        $campaignDraft = $this->campaignDraftService->approve($draft, $request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM campaign draft approved successfully.',
            'draft' => $this->campaignDraftService->payload($campaignDraft, $request->user(), true),
        ]);
    }

    public function reject(RejectCrmCampaignDraftRequest $request, int $draft): JsonResponse
    {
        $campaignDraft = $this->campaignDraftService->reject($draft, $request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM campaign draft rejected successfully.',
            'draft' => $this->campaignDraftService->payload($campaignDraft, $request->user(), true),
        ]);
    }

    public function returnToDraft(ReturnCrmCampaignDraftToDraftRequest $request, int $draft): JsonResponse
    {
        $campaignDraft = $this->campaignDraftService->returnToDraft($draft, $request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM campaign returned to draft planning successfully.',
            'draft' => $this->campaignDraftService->payload($campaignDraft, $request->user(), true),
        ]);
    }

    public function refreshAudienceSnapshot(RefreshCrmCampaignDraftAudienceSnapshotRequest $request, int $draft): JsonResponse
    {
        $campaignDraft = $this->campaignDraftService->refreshAudienceSnapshot($draft, $request->user());

        return response()->json([
            'message' => 'CRM campaign audience snapshot refreshed successfully. Review submission and independent reapproval are required before dispatch preparation.',
            'draft' => $this->campaignDraftService->payload($campaignDraft, $request->user(), true),
        ]);
    }

    public function store(StoreCrmCampaignDraftRequest $request): JsonResponse
    {
        $draft = $this->campaignDraftService->create($request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM campaign draft created successfully.',
            'draft' => $this->campaignDraftService->payload($draft, $request->user(), true),
        ], 201);
    }

    public function update(UpdateCrmCampaignDraftRequest $request, int $draft): JsonResponse
    {
        $campaignDraft = $this->campaignDraftService->update($draft, $request->validated(), $request->user());

        return response()->json([
            'message' => 'CRM campaign draft updated successfully.',
            'draft' => $this->campaignDraftService->payload($campaignDraft, $request->user(), true),
        ]);
    }

    public function archive(ArchiveCrmCampaignDraftRequest $request, int $draft): JsonResponse
    {
        $campaignDraft = $this->campaignDraftService->archive($draft, $request->user());

        return response()->json([
            'message' => 'CRM campaign draft archived successfully.',
            'draft' => $this->campaignDraftService->payload($campaignDraft, $request->user(), true),
        ]);
    }
}
