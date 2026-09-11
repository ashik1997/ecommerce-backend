<?php

namespace App\Services\FbMarketing;

use App\Jobs\FbMarketing\RunFbmOperationalActionJob;
use App\Models\FbMarketing\FbmCampaignOperationalAction;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FbmCampaignOperationalActionDispatchService
{
    public function __construct(
        protected FbmQueueReadinessService $queueReadiness
    ) {
    }

    public function dispatch(FbmCampaignOperationalAction $action): FbmCampaignOperationalAction
    {
        $this->queueReadiness->assertCurrentReady();

        if (in_array((string) $action->status, ['completed', 'running', 'queued_for_provider_worker'], true)) {
            return $action;
        }

        $action->forceFill([
            'status' => 'queued_for_provider_worker',
            'redacted_message' => 'Operational action queued for the controlled provider writer.',
        ])->save();

        try {
            dispatch(new RunFbmOperationalActionJob((string) $action->action_uuid));
        } catch (Throwable $exception) {
            $action->forceFill([
                'status' => 'retryable_failed',
                'redacted_message' => 'Operational action could not be queued. Review queue readiness before retrying.',
            ])->save();

            Log::warning('FB MARKETING operational action enqueue failed safely.', [
                'fbm_campaign_operational_action_id' => (int) $action->id,
                'exception_class' => get_class($exception),
            ]);

            throw new RuntimeException('FB MARKETING could not enqueue the operational action worker. Review dedicated queue readiness.');
        }

        return $action->fresh();
    }
}
