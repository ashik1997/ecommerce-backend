<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Services\RoleSidebarPermissionService;
use App\Services\FbMarketing\FbmWebhookReconciliationAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FbMarketingAlertController extends Controller
{
    public function index(Request $request, FbmWebhookReconciliationAlertService $service, RoleSidebarPermissionService $permissions): View
    {
        return view('backend.fb-marketing.alerts', [
            'report' => $service->build(),
            'canReconcile' => $permissions->userCan($request->user(), 'fb_marketing_alert_reconcile', 'update'),
        ]);
    }

    public function reconcile(Request $request, FbmWebhookReconciliationAlertService $service): RedirectResponse
    {
        $run = $service->runReconciliation(optional($request->user())->id);

        return redirect()
            ->route('fbMarketing.alerts.index')
            ->with('success', 'Ad-account reconciliation completed. ' . (int) $run->alert_count . ' local alert(s) refreshed.');
    }
}
