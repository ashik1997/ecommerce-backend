<?php

namespace App\Services\FbMarketing;

use App\Jobs\FbMarketing\DeliverFbmConversionEventJob;
use App\Models\FbMarketing\FbmConversionEvent;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FbmConversionEventDispatchService
{
    public function __construct(
        protected FbmQueueReadinessService $queueReadiness
    ) {
    }

    public function dispatch(FbmConversionEvent $event): FbmConversionEvent
    {
        $this->queueReadiness->assertCurrentReady();

        if ($event->isDelivered()) {
            return $event;
        }

        $event->forceFill(['status' => FbmConversionEvent::STATUS_QUEUED])->save();

        try {
            dispatch(new DeliverFbmConversionEventJob((string) $event->event_uuid));
        } catch (Throwable $exception) {
            $event->forceFill(['status' => FbmConversionEvent::STATUS_RETRYABLE_FAILED])->save();

            Log::warning('FB MARKETING CAPI event enqueue failed safely.', [
                'fbm_conversion_event_id' => (int) $event->id,
                'exception_class' => get_class($exception),
            ]);

            throw new RuntimeException('FB MARKETING could not enqueue the CAPI event. Use the manual no-queue retry after reviewing queue readiness.');
        }

        return $event->fresh();
    }
}
