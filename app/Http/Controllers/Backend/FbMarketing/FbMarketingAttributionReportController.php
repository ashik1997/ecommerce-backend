<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FbMarketing\FbMarketingAttributionReportFilterRequest;
use App\Services\FbMarketing\FbmAttributionReportService;
use App\Services\FbMarketing\FbmOrderAttributionBridgeService;
use App\Services\RoleSidebarPermissionService;
use App\Support\Security\SecretRedactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class FbMarketingAttributionReportController extends Controller
{
    public function index(
        FbMarketingAttributionReportFilterRequest $request,
        FbmAttributionReportService $reports,
        RoleSidebarPermissionService $permissions
    ): View {
        return view('backend.fb-marketing.attribution-reports', [
            'report' => $reports->build($request->validated()),
            'canReconcile' => $permissions->userCan($request->user(), 'fb_marketing_attribution_reports_reconcile', 'update'),
        ]);
    }

    public function reconcile(Request $request, FbmOrderAttributionBridgeService $bridge): RedirectResponse
    {
        try {
            $summary = $bridge->reconcileRecent();
        } catch (Throwable $exception) {
            $message = trim(SecretRedactor::redactString($exception->getMessage()));

            return redirect()
                ->route('fbMarketing.attribution-reports.index', $request->query())
                ->with('error', $message !== '' ? substr($message, 0, 500) : 'Attribution reconciliation stopped safely.');
        }

        return redirect()
            ->route('fbMarketing.attribution-reports.index', $request->query())
            ->with('success', 'Recent ERP attribution reconciliation completed safely: '
                . (int) $summary['bridged'] . ' bridged row(s), '
                . (int) $summary['warning_count'] . ' warning(s).');
    }
}
