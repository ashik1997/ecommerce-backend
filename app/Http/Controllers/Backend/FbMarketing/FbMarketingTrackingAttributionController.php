<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FbMarketing\CreateFbmCapiDiagnosticRequest;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmConversionEvent;
use App\Models\FbMarketing\FbmConversionEventAttempt;
use App\Services\FbMarketing\FbmBrowserPixelContractService;
use App\Services\FbMarketing\FbmConversionEventDispatchService;
use App\Services\FbMarketing\FbmConversionEventReadinessService;
use App\Services\FbMarketing\FbmConversionEventService;
use App\Services\FbMarketing\FbmLandingAttributionService;
use App\Services\FbMarketing\FbmOrderAttributionBridgeService;
use App\Services\FbMarketing\FbmQueueReadinessService;
use App\Services\RoleSidebarPermissionService;
use App\Support\Security\SecretRedactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class FbMarketingTrackingAttributionController extends Controller
{
    public function index(
        Request $request,
        RoleSidebarPermissionService $permissions,
        FbmLandingAttributionService $landingAttribution,
        FbmBrowserPixelContractService $browserPixel,
        FbmConversionEventReadinessService $capiReadiness,
        FbmQueueReadinessService $queueReadiness,
        FbmOrderAttributionBridgeService $orderAttribution
    ): View {
        $summary = $capiReadiness->operationalSummary();
        $events = collect();
        $attempts = collect();
        $connections = collect();

        if ($summary['schema_ready']) {
            $events = FbmConversionEvent::query()
                ->with(['connection:id,connection_name', 'latestAttempt'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(max(1, (int) config('fb_marketing.conversions_api.event_history_limit', 100)))
                ->get()
                ->map(fn(FbmConversionEvent $event): array => $event->toSafeSummary());

            $attempts = FbmConversionEventAttempt::query()
                ->with('event:id,event_uuid,event_name')
                ->orderByDesc('attempted_at')
                ->orderByDesc('id')
                ->limit(max(1, (int) config('fb_marketing.conversions_api.attempt_history_limit', 100)))
                ->get()
                ->map(fn(FbmConversionEventAttempt $attempt): array => $attempt->toSafeSummary());
        }

        if (Schema::hasTable('fbm_connections')) {
            $connections = FbmConnection::query()
                ->where('is_active', true)
                ->orderBy('connection_name')
                ->get()
                ->map(fn(FbmConnection $connection): array => $connection->toSafeSummary());
        }

        return view('backend.fb-marketing.tracking-attribution', [
            'landingAttributionSummary' => $landingAttribution->operationalSummary(),
            'browserPixelSummary' => $browserPixel->operationalSummary(),
            'capiSummary' => $summary,
            'queueReadiness' => $queueReadiness->currentSummary(),
            'orderAttributionSummary' => $orderAttribution->operationalSummary(),
            'orderAttributions' => $orderAttribution->recentSafeSummaries(),
            'events' => $events,
            'attempts' => $attempts,
            'connections' => $connections,
            'canRunDiagnostic' => $permissions->userCan($request->user(), 'fb_marketing_capi_diagnostic_run', 'create'),
            'canDispatchEvent' => $permissions->userCan($request->user(), 'fb_marketing_capi_event_dispatch', 'create'),
            'canRetryEvent' => $permissions->userCan($request->user(), 'fb_marketing_capi_event_retry', 'update'),
            'canReconcileOrderAttribution' => $permissions->userCan($request->user(), 'fb_marketing_order_attribution_reconcile', 'update'),
        ]);
    }

    public function reconcileRecentOrders(Request $request, FbmOrderAttributionBridgeService $bridge): RedirectResponse
    {
        try {
            $summary = $bridge->reconcileRecent();
        } catch (Throwable $exception) {
            return $this->redirectWithSafeError($exception);
        }

        return redirect()
            ->route('fbMarketing.tracking-attribution.index')
            ->with('success', 'Recent ERP attribution reconciliation completed safely: '
                . (int) $summary['bridged'] . ' bridged row(s), '
                . (int) $summary['warning_count'] . ' warning(s).');
    }

    public function createDiagnostic(
        CreateFbmCapiDiagnosticRequest $request,
        FbmConversionEventService $events
    ): RedirectResponse {
        $data = $request->validated();

        try {
            $event = $events->createDiagnostic(
                FbmConnection::query()->findOrFail((int) $data['connection']),
                (string) $data['diagnostic_mode'],
                $request->user()
            );
        } catch (Throwable $exception) {
            return $this->redirectWithSafeError($exception);
        }

        return redirect()
            ->route('fbMarketing.tracking-attribution.index')
            ->with('success', 'CAPI diagnostic completed with safe status: ' . strtoupper((string) $event->status) . '.');
    }

    public function retryNow(Request $request, string $eventUuid, FbmConversionEventService $events): RedirectResponse
    {
        try {
            $event = $events->deliverNow($eventUuid, 'manual');
        } catch (Throwable $exception) {
            return $this->redirectWithSafeError($exception);
        }

        return redirect()
            ->route('fbMarketing.tracking-attribution.index')
            ->with('success', 'Manual no-queue CAPI attempt completed with safe status: ' . strtoupper((string) $event->status) . '.');
    }

    public function dispatchEvent(Request $request, string $eventUuid, FbmConversionEventDispatchService $dispatch): RedirectResponse
    {
        try {
            $event = FbmConversionEvent::query()->where('event_uuid', $eventUuid)->firstOrFail();
            $event = $dispatch->dispatch($event);
        } catch (Throwable $exception) {
            return $this->redirectWithSafeError($exception);
        }

        return redirect()
            ->route('fbMarketing.tracking-attribution.index')
            ->with('success', 'CAPI event queued with safe status: ' . strtoupper((string) $event->status) . '.');
    }

    private function redirectWithSafeError(Throwable $exception): RedirectResponse
    {
        $message = trim(SecretRedactor::redactString($exception->getMessage()));
        $message = $message !== ''
            ? substr($message, 0, 500)
            : 'FB MARKETING CAPI action stopped safely. Review the redacted attempt ledger.';

        return redirect()
            ->route('fbMarketing.tracking-attribution.index')
            ->with('error', $message);
    }
}
