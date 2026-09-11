<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmApiRequestLog;
use App\Models\FbMarketing\FbmInsightReportRun;
use App\Models\FbMarketing\FbmSyncRun;
use Illuminate\Support\Facades\Schema;

class FbmPerformanceHealthService
{
    public function summary(): array
    {
        $latestSync = null;
        $latestReport = null;
        $recentApiErrors = collect();

        if (Schema::hasTable('fbm_sync_runs')) {
            $sync = FbmSyncRun::query()
                ->with('connection:id,connection_name')
                ->orderByDesc('requested_at')
                ->orderByDesc('id')
                ->first();
            $latestSync = $sync ? $sync->toSafeSummary() : null;
        }

        if (Schema::hasTable('fbm_insight_report_runs')) {
            $report = FbmInsightReportRun::query()
                ->with(['connection:id,connection_name', 'adAccount:id,asset_name'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();
            $latestReport = $report ? $report->toSafeSummary() : null;
        }

        if (Schema::hasTable('fbm_api_request_logs')) {
            $recentApiErrors = FbmApiRequestLog::query()
                ->where(function ($query) {
                    $query->whereNotNull('provider_error_code')
                        ->orWhere('http_status', '>=', 400)
                        ->orWhereNull('http_status');
                })
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(max(1, min(30, (int) config('fb_marketing.performance_worklists.api_error_history_limit', 10))))
                ->get()
                ->map(fn(FbmApiRequestLog $log): array => $log->toSafeSummary());
        }

        return [
            'latest_sync' => $latestSync,
            'latest_report' => $latestReport,
            'recent_api_errors' => $recentApiErrors,
            'recent_api_error_count' => $recentApiErrors->count(),
        ];
    }
}
