<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FbMarketing\FbMarketingAudienceRequest;
use App\Http\Requests\Backend\FbMarketing\StoreFbmAudienceRequest;
use App\Http\Requests\Backend\FbMarketing\UpdateFbmAudienceSelectionRequest;
use App\Models\FbMarketing\FbmConnection;
use App\Services\FbMarketing\FbmAudienceManagementService;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class FbMarketingAudienceController extends Controller
{
    public function index(
        FbMarketingAudienceRequest $request,
        FbmAudienceManagementService $audiences,
        RoleSidebarPermissionService $permissions
    ): View {
        return view('backend.fb-marketing.audiences', [
            'report' => $audiences->build($request->validated()),
            'canManageAudiences' => $permissions->userCan($request->user(), 'fb_marketing_audience_manage', 'create'),
            'canManageSelection' => $permissions->userCan($request->user(), 'fb_marketing_audience_selection_manage', 'update'),
            'canRunSync' => $permissions->userCan($request->user(), 'fb_marketing_audience_sync_run', 'read'),
        ]);
    }

    public function store(StoreFbmAudienceRequest $request, FbmAudienceManagementService $audiences): RedirectResponse
    {
        $audiences->storeAudience($request->validated(), optional($request->user())->id);

        return redirect()->route('fbMarketing.audiences.index', $request->query())->with('success', 'Local audience plan saved.');
    }

    public function updateAudienceSelection(
        UpdateFbmAudienceSelectionRequest $request,
        int $audience,
        FbmAudienceManagementService $audiences
    ): RedirectResponse {
        $audiences->updateAudienceSelection($audience, $request->validated(), optional($request->user())->id);

        return redirect()->route('fbMarketing.audiences.index', $request->query())->with('success', 'Audience selection updated locally.');
    }

    public function updateProductSetSelection(
        UpdateFbmAudienceSelectionRequest $request,
        int $productSet,
        FbmAudienceManagementService $audiences
    ): RedirectResponse {
        $audiences->updateProductSetSelection($productSet, $request->validated(), optional($request->user())->id);

        return redirect()->route('fbMarketing.audiences.index', $request->query())->with('success', 'Product-set selection updated locally.');
    }

    public function sync(int $connection, FbmAudienceManagementService $audiences): RedirectResponse
    {
        try {
            $result = $audiences->syncAudiences(FbmConnection::query()->findOrFail($connection), request()->user());
        } catch (Throwable) {
            return redirect()->route('fbMarketing.audiences.index')
                ->with('error', 'Read-only audience sync stopped safely. Review the redacted sync ledger before retrying.');
        }

        $status = (string) ($result['status'] ?? 'failed');
        $message = match ($status) {
            'success' => 'Read-only audience sync completed.',
            'partial_success' => 'Read-only audience sync completed with safe warnings.',
            'skipped' => 'Read-only audience sync was skipped safely because no selected ad account is ready.',
            default => 'Read-only audience sync stopped safely. Review the redacted sync ledger before retrying.',
        };

        return redirect()->route('fbMarketing.audiences.index')
            ->with(in_array($status, ['success', 'partial_success'], true) ? 'success' : 'error', $message);
    }
}
