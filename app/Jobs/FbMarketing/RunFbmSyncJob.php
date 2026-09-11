<?php

namespace App\Jobs\FbMarketing;

use App\Services\FbMarketing\FbmSyncExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunFbmSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;
    public int $timeout;
    public array $backoff;

    public function __construct(public string $runUuid)
    {
        $this->tries = max(1, min(10, (int) config('fb_marketing.queue.tries', 3)));
        $this->timeout = max(30, min(3600, (int) config('fb_marketing.queue.timeout_seconds', 300)));
        $this->backoff = array_values(array_map(
            fn($seconds) => max(1, (int) $seconds),
            (array) config('fb_marketing.queue.backoff_seconds', [30, 120, 300])
        ));

        $this->onConnection((string) config('fb_marketing.queue.connection', 'fb-marketing'));
        $this->onQueue((string) config('fb_marketing.queue.name', 'fb-marketing'));
    }

    public function handle(FbmSyncExecutionService $execution): void
    {
        $execution->execute($this->runUuid);
    }

    public function failed(Throwable $exception): void
    {
        try {
            app(FbmSyncExecutionService::class)->markFailed(
                $this->runUuid,
                'Read-only FB MARKETING sync exhausted its bounded queue attempts and stopped safely.'
            );
        } catch (Throwable $failure) {
            report($failure);
        }
    }
}
