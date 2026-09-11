<?php

namespace App\Jobs\FbMarketing;

use App\Services\FbMarketing\FbmConversionEventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class DeliverFbmConversionEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;
    public int $timeout;
    public array $backoff;

    public function __construct(public string $eventUuid)
    {
        $this->tries = max(1, min(10, (int) config('fb_marketing.conversions_api.tries', 3)));
        $this->timeout = max(15, min(600, (int) config('fb_marketing.conversions_api.timeout_seconds', 60)));
        $this->backoff = array_values(array_map(
            fn($seconds) => max(1, (int) $seconds),
            (array) config('fb_marketing.conversions_api.backoff_seconds', [30, 120, 300])
        ));

        $this->onConnection((string) config('fb_marketing.queue.connection', 'fb-marketing'));
        $this->onQueue((string) config('fb_marketing.queue.name', 'fb-marketing'));
    }

    public function handle(FbmConversionEventService $events): void
    {
        $events->deliverQueued($this->eventUuid);
    }

    public function failed(Throwable $exception): void
    {
        try {
            app(FbmConversionEventService::class)->markQueueExhausted($this->eventUuid);
        } catch (Throwable $failure) {
            report($failure);
        }
    }
}
