<?php

namespace App\Jobs\FbMarketing;

use App\Services\FbMarketing\FbmCampaignOperationalActionWorkerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunFbmOperationalActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;
    public int $timeout;
    public array $backoff;

    public function __construct(public string $actionUuid)
    {
        $this->tries = max(1, min(10, (int) config('fb_marketing.operational_actions.tries', 3)));
        $this->timeout = max(30, min(900, (int) config('fb_marketing.operational_actions.timeout_seconds', 120)));
        $this->backoff = array_values(array_map(
            fn($seconds) => max(1, (int) $seconds),
            (array) config('fb_marketing.operational_actions.backoff_seconds', [30, 120, 300])
        ));

        $this->onConnection((string) config('fb_marketing.queue.connection', 'fb-marketing'));
        $this->onQueue((string) config('fb_marketing.queue.name', 'fb-marketing'));
    }

    public function handle(FbmCampaignOperationalActionWorkerService $worker): void
    {
        $worker->executeQueued($this->actionUuid);
    }

    public function failed(Throwable $exception): void
    {
        try {
            app(FbmCampaignOperationalActionWorkerService::class)->markQueueExhausted($this->actionUuid);
        } catch (Throwable $failure) {
            report($failure);
        }
    }
}
