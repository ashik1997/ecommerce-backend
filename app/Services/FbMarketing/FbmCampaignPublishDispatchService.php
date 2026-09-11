<?php

namespace App\Services\FbMarketing;

use App\Jobs\FbMarketing\PublishFbmCampaignAttemptJob;
use App\Models\FbMarketing\FbmCampaignPublishAttempt;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FbmCampaignPublishDispatchService
{
    public function __construct(
        protected FbmQueueReadinessService $queueReadiness
    ) {
    }

    public function dispatch(FbmCampaignPublishAttempt $attempt): FbmCampaignPublishAttempt
    {
        $this->queueReadiness->assertCurrentReady();

        if (in_array((string) $attempt->status, ['completed', 'running', 'queued_for_provider_worker'], true)) {
            return $attempt;
        }

        $attempt->forceFill([
            'status' => 'queued_for_provider_worker',
            'safe_response_summary' => array_merge(is_array($attempt->safe_response_summary) ? $attempt->safe_response_summary : [], [
                'worker_queued' => true,
                'provider_request_count' => 0,
            ]),
            'redacted_message' => 'Campaign publish attempt queued for the controlled provider writer.',
        ])->save();

        try {
            dispatch(new PublishFbmCampaignAttemptJob((string) $attempt->attempt_uuid));
        } catch (Throwable $exception) {
            $attempt->forceFill([
                'status' => 'retryable_failed',
                'redacted_message' => 'Campaign publish attempt could not be queued. Review queue readiness before retrying.',
            ])->save();

            Log::warning('FB MARKETING campaign publish enqueue failed safely.', [
                'fbm_campaign_publish_attempt_id' => (int) $attempt->id,
                'exception_class' => get_class($exception),
            ]);

            throw new RuntimeException('FB MARKETING could not enqueue the campaign publish worker. Review dedicated queue readiness.');
        }

        return $attempt->fresh();
    }
}
