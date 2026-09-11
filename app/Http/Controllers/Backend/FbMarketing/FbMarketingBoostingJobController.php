<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FbMarketing\FbMarketingBoostingJobFilterRequest;
use App\Http\Requests\Backend\FbMarketing\StoreFbmBoostingJobCampaignRequest;
use App\Http\Requests\Backend\FbMarketing\StoreFbmBoostingJobCostRequest;
use App\Http\Requests\Backend\FbMarketing\StoreFbmBoostingJobPaymentRequest;
use App\Http\Requests\Backend\FbMarketing\StoreFbmBoostingJobRequest;
use App\Services\FbMarketing\FbmBoostingJobLedgerService;
use App\Services\RoleSidebarPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FbMarketingBoostingJobController extends Controller
{
    public function index(
        FbMarketingBoostingJobFilterRequest $request,
        FbmBoostingJobLedgerService $ledger,
        RoleSidebarPermissionService $permissions
    ): View {
        return view('backend.fb-marketing.boosting-jobs', [
            'report' => $ledger->build($request->validated()),
            'canManageJobs' => $permissions->userCan($request->user(), 'fb_marketing_boosting_jobs_manage', 'create'),
            'canManageLedger' => $permissions->userCan($request->user(), 'fb_marketing_boosting_job_ledger_manage', 'create'),
        ]);
    }

    public function store(StoreFbmBoostingJobRequest $request, FbmBoostingJobLedgerService $ledger): RedirectResponse
    {
        $ledger->storeJob($request->validated(), optional($request->user())->id);

        return redirect()->route('fbMarketing.boosting-jobs.index', $request->query())->with('success', 'Boosting job saved safely.');
    }

    public function attachCampaign(StoreFbmBoostingJobCampaignRequest $request, FbmBoostingJobLedgerService $ledger): RedirectResponse
    {
        $ledger->attachCampaign($request->validated(), optional($request->user())->id);

        return redirect()->route('fbMarketing.boosting-jobs.index', $request->query())->with('success', 'Local campaign link saved safely.');
    }

    public function storePayment(StoreFbmBoostingJobPaymentRequest $request, FbmBoostingJobLedgerService $ledger): RedirectResponse
    {
        $ledger->storePayment($request->validated(), optional($request->user())->id);

        return redirect()->route('fbMarketing.boosting-jobs.index', $request->query())->with('success', 'Boosting job payment saved safely.');
    }

    public function storeCost(StoreFbmBoostingJobCostRequest $request, FbmBoostingJobLedgerService $ledger): RedirectResponse
    {
        $ledger->storeCost($request->validated(), optional($request->user())->id);

        return redirect()->route('fbMarketing.boosting-jobs.index', $request->query())->with('success', 'Boosting job cost saved safely.');
    }
}
