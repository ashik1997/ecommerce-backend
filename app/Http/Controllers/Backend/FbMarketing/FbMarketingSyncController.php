<?php

namespace App\Http\Controllers\Backend\FbMarketing;

use App\Http\Controllers\Controller;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmSyncRun;
use App\Services\FbMarketing\FbmSyncDispatchService;
use App\Services\FbMarketing\FbmSyncExecutionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class FbMarketingSyncController extends Controller
{
    public function queueSync(Request $request, int $connection, FbmSyncDispatchService $dispatch): RedirectResponse
    {
        $run = $dispatch->dispatch(
            FbmConnection::query()->findOrFail($connection),
            $request->user()
        );

        $message = $run->wasRecentlyCreated
            ? 'Read-only FB MARKETING sync queued. Provider calls will run through the dedicated worker.'
            : 'A read-only FB MARKETING sync is already queued or running for this connection.';

        return redirect()
            ->route('fbMarketing.configuration.index')
            ->with('success', $message);
    }

    public function runManualNow(Request $request, int $connection, FbmSyncExecutionService $execution): RedirectResponse
    {
        try {
            $run = $execution->executeManualDirect(
                FbmConnection::query()->findOrFail($connection),
                $request->user()
            );
        } catch (Throwable) {
            return redirect()
                ->route('fbMarketing.configuration.index')
                ->with('error', 'Manual no-queue sync stopped safely. Review the redacted sync and API ledgers, then retry after correcting the reported readiness issue.');
        }

        $message = match ((string) $run->status) {
            FbmSyncRun::STATUS_SUCCESS => 'Manual no-queue sync completed. Recent account-level dashboard snapshots were refreshed without dispatching a worker job.',
            FbmSyncRun::STATUS_PARTIAL_SUCCESS => 'Manual no-queue sync completed with safe warnings. Review the sync ledger; historical backfill was intentionally not queued.',
            FbmSyncRun::STATUS_SKIPPED => 'Manual no-queue sync was skipped safely because another read-only sync currently owns the same connection lock.',
            FbmSyncRun::STATUS_QUEUED, FbmSyncRun::STATUS_RUNNING => 'A manual no-queue sync is already running for this connection.',
            default => 'Manual no-queue sync stopped safely. Review the redacted sync ledger before retrying.',
        };

        return redirect()
            ->route('fbMarketing.configuration.index')
            ->with(in_array((string) $run->status, [FbmSyncRun::STATUS_SUCCESS, FbmSyncRun::STATUS_PARTIAL_SUCCESS], true) ? 'success' : 'error', $message);
    }

    public function runManualDrilldownsNow(Request $request, int $connection, FbmSyncExecutionService $execution): RedirectResponse
    {
        try {
            $run = $execution->executeManualDrilldownDirect(
                FbmConnection::query()->findOrFail($connection),
                $request->user()
            );
        } catch (Throwable) {
            return redirect()
                ->route('fbMarketing.configuration.index')
                ->with('error', 'Manual drilldown refresh stopped safely. Review the redacted sync and API ledgers, then retry after correcting the reported readiness issue.');
        }

        $message = match ((string) $run->status) {
            FbmSyncRun::STATUS_SUCCESS => 'Manual drilldown refresh completed. Recent campaign, ad-set and ad snapshots were refreshed without dispatching a worker job.',
            FbmSyncRun::STATUS_PARTIAL_SUCCESS => 'Manual drilldown refresh completed with safe warnings. Review the sync ledger; historical backfill was intentionally not queued.',
            FbmSyncRun::STATUS_SKIPPED => 'Manual drilldown refresh was skipped safely because another read-only sync currently owns the same connection lock.',
            FbmSyncRun::STATUS_QUEUED, FbmSyncRun::STATUS_RUNNING => 'A manual drilldown refresh is already running for this connection.',
            default => 'Manual drilldown refresh stopped safely. Review the redacted sync ledger before retrying.',
        };

        return redirect()
            ->route('fbMarketing.configuration.index')
            ->with(in_array((string) $run->status, [FbmSyncRun::STATUS_SUCCESS, FbmSyncRun::STATUS_PARTIAL_SUCCESS], true) ? 'success' : 'error', $message);
    }
}
