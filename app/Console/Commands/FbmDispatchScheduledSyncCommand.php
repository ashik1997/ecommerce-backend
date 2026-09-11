<?php

namespace App\Console\Commands;

use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmModuleSetting;
use App\Models\FbMarketing\FbmSyncRun;
use App\Services\FbMarketing\FbmQueueReadinessService;
use App\Services\FbMarketing\FbmSyncDispatchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FbmDispatchScheduledSyncCommand extends Command
{
    protected $signature = 'fb-marketing:dispatch-scheduled-sync';
    protected $description = 'Queue due read-only FB MARKETING sync runs without performing provider calls in the scheduler process.';

    public function handle(
        FbmQueueReadinessService $readiness,
        FbmSyncDispatchService $dispatch
    ): int {
        if (!(bool) config('fb_marketing.scheduler.enabled', false)) {
            $this->line('FB MARKETING scheduler is disabled by deployment policy.');

            return self::SUCCESS;
        }

        $summary = $readiness->currentSummary();
        if (!$summary['ready']) {
            $this->error('FB MARKETING queue readiness failed. No sync was queued.');

            return self::FAILURE;
        }

        $queued = 0;
        $skipped = 0;
        $failed = 0;

        if (!$this->isDue()) {
            $skipped = 1;
        } else {
            foreach (FbmConnection::query()->where('is_active', true)->get() as $connection) {
                try {
                    $run = $dispatch->dispatch($connection, null, FbmSyncRun::TRIGGER_SCHEDULED);
                    $queued += $run->wasRecentlyCreated ? 1 : 0;
                } catch (Throwable $exception) {
                    Log::warning('FB MARKETING scheduled dispatch failed safely.', [
                        'fbm_connection_id' => (int) $connection->id,
                        'exception_class' => get_class($exception),
                    ]);
                    $failed++;
                }
            }

            FbmModuleSetting::query()->where('id', 1)->update([
                'last_scheduled_sync_dispatched_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info("FB MARKETING scheduled dispatch complete. queued={$queued}, skipped={$skipped}, failed={$failed}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    protected function isDue(): bool
    {
        if (!Schema::hasTable('fbm_module_settings')) {
            return false;
        }

        $settings = FbmModuleSetting::query()->find(1);
        if (!$settings || !$settings->scheduled_sync_enabled) {
            return false;
        }

        $minutes = max(15, min(1440, (int) $settings->scheduled_sync_interval_minutes));
        if (!$settings->last_scheduled_sync_dispatched_at) {
            return true;
        }

        return $settings->last_scheduled_sync_dispatched_at->lte(now()->subMinutes($minutes));
    }
}
