<?php

namespace App\Jobs\FbMarketing;

use App\Services\FbMarketing\FbmCampaignPublishWorkerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PublishFbmCampaignAttemptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;
    public int $timeout;
    public array $backoff;

    public function __construct(public string $attemptUuid)
    {
        $this->tries = max(1, min(10, (int) config('fb_marketing.campaign_publish.tries', 3)));
        $this->timeout = max(30, min(1200, (int) config('fb_marketing.campaign_publish.timeout_seconds', 180)));
        $this->backoff = array_values(array_map(
            fn($seconds) => max(1, (int) $seconds),
            (array) config('fb_marketing.campaign_publish.backoff_seconds', [30, 120, 300])
        ));

        $this->onConnection((string) config('fb_marketing.queue.connection', 'fb-marketing'));
        $this->onQueue((string) config('fb_marketing.queue.name', 'fb-marketing'));
    }

    public function handle(FbmCampaignPublishWorkerService $worker): void
    {
        $worker->executeQueued($this->attemptUuid);
    }

    public function failed(Throwable $exception): void
    {
        try {
            app(FbmCampaignPublishWorkerService::class)->markQueueExhausted($this->attemptUuid);
        } catch (Throwable $failure) {
            report($failure);
        }
    }
}
