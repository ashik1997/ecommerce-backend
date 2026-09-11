<?php

namespace App\Services\FbMarketing;

use App\Exceptions\FbMarketing\FbmRetryableCampaignPublishException;
use App\Models\FbMarketing\FbmAd;
use App\Models\FbMarketing\FbmAdAccount;
use App\Models\FbMarketing\FbmAdSet;
use App\Models\FbMarketing\FbmAudience;
use App\Models\FbMarketing\FbmCampaign;
use App\Models\FbMarketing\FbmCampaignDraft;
use App\Models\FbMarketing\FbmCampaignPublishAttempt;
use App\Models\FbMarketing\FbmCampaignPublishStep;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmCreative;
use App\Models\FbMarketing\FbmCreativeAsset;
use App\Models\FbMarketing\FbmPage;
use App\Support\Security\SecretRedactor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class FbmCampaignPublishWorkerService
{
    public function __construct(
        protected FbmProviderWriterReadinessService $providerWriterReadiness,
        protected FbmCampaignPublishProviderClient $providerClient,
        protected FbmProviderWriteSafetyService $writeSafety
    ) {
    }

    public function executeQueued(string $attemptUuid): FbmCampaignPublishAttempt
    {
        $attempt = $this->execute($attemptUuid, 'queue');

        if ((string) $attempt->status === 'retryable_failed') {
            throw new FbmRetryableCampaignPublishException('FB MARKETING campaign publish failed with a retryable safe status.');
        }

        return $attempt;
    }

    public function execute(string $attemptUuid, string $origin = 'manual'): FbmCampaignPublishAttempt
    {
        $attempt = $this->attempt($attemptUuid);
        $lock = Cache::lock('fbm:campaign-publish:' . $this->hmac((string) $attempt->attempt_uuid), $this->lockSeconds());

        if (!$lock->get()) {
            throw new RuntimeException('Another FB MARKETING campaign publish worker currently owns this attempt.');
        }

        try {
            return $this->executeLocked($attempt->fresh(['draft.assets', 'snapshot', 'steps']), $origin);
        } finally {
            optional($lock)->release();
        }
    }

    public function markQueueExhausted(string $attemptUuid): void
    {
        if (!$this->schemaReady()) {
            return;
        }

        FbmCampaignPublishAttempt::query()
            ->where('attempt_uuid', $this->normalizeUuid($attemptUuid))
            ->where('status', 'retryable_failed')
            ->update([
                'status' => 'partial_failed',
                'redacted_message' => 'Campaign publish exhausted bounded queue attempts and stopped safely.',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function executeLocked(FbmCampaignPublishAttempt $attempt, string $origin): FbmCampaignPublishAttempt
    {
        if ((string) $attempt->status === 'completed') {
            return $attempt;
        }

        if (!(bool) config('fb_marketing.campaign_publish.provider_writes_enabled', false)) {
            return $this->blockAttempt($attempt, 'provider_writes_disabled', 'Provider writes are disabled by configuration.');
        }

        $writerReadiness = $this->providerWriterReadiness->assertReadyFor('campaign_publish');
        if (empty($writerReadiness['ready'])) {
            return $this->blockAttempt($attempt, (string) ($writerReadiness['reason'] ?? 'provider_writer_not_ready'), (string) $writerReadiness['message']);
        }

        try {
            $context = $this->publishContext($attempt);
            $this->preflight($context);
        } catch (Throwable $exception) {
            return $this->blockAttempt($attempt, 'provider_publish_preflight_failed', $exception->getMessage());
        }

        $attempt->forceFill([
            'status' => 'running',
            'execution_mode' => 'provider_write',
            'redacted_message' => 'Campaign publish worker is executing allow-listed Meta Marketing API steps.',
            'started_at' => $attempt->started_at ?: now(),
            'completed_at' => null,
        ])->save();

        $providerRequestCount = 0;
        foreach (['campaign', 'ad_set', 'creative', 'ad'] as $stepKey) {
            $step = $attempt->steps->firstWhere('step_key', $stepKey) ?: $attempt->steps()->where('step_key', $stepKey)->first();
            if (!$step) {
                return $this->failAttempt($attempt, null, 'partial_failed', 'Publish step ledger is incomplete.', false);
            }
            if ((string) $step->status === 'completed') {
                continue;
            }

            try {
                $result = $this->executeStep($attempt->fresh(), $step, $context, $origin);
            } catch (Throwable $exception) {
                return $this->failAttempt($attempt->fresh(), $step, 'partial_failed', $exception->getMessage(), false, $providerRequestCount);
            }
            $providerRequestCount++;
            if (empty($result['successful'])) {
                return $this->failAttempt(
                    $attempt->fresh(),
                    $step,
                    !empty($result['retryable']) ? 'retryable_failed' : 'partial_failed',
                    (string) ($result['redacted_message'] ?? 'Campaign publish step failed safely.'),
                    !empty($result['retryable']),
                    $providerRequestCount
                );
            }
        }

        $attempt->forceFill([
            'status' => 'completed',
            'safe_response_summary' => [
                'provider_request_count' => $providerRequestCount,
                'worker_origin' => $origin,
                'campaign_local_id' => $attempt->fresh()->provider_campaign_local_id,
                'ad_set_local_id' => $attempt->fresh()->provider_ad_set_local_id,
                'creative_local_id' => $attempt->fresh()->provider_creative_local_id,
                'ad_local_id' => $attempt->fresh()->provider_ad_local_id,
                'rollback_plan' => $this->writeSafety->publishRollbackPlan($attempt->fresh(['steps'])),
                'reconciliation' => $this->writeSafety->publishReconciliationSummary($attempt),
            ],
            'redacted_message' => 'Campaign, ad set, creative and ad were created in Meta in PAUSED status.',
            'completed_at' => now(),
        ])->save();

        return $attempt->fresh();
    }

    private function executeStep(FbmCampaignPublishAttempt $attempt, FbmCampaignPublishStep $step, array $context, string $origin): array
    {
        $stepKey = (string) $step->step_key;
        $payload = $this->payloadFor($stepKey, $attempt, $context);
        $edge = $this->edgeFor($stepKey, $context['ad_account_node']);
        $operationKey = 'campaign_publish_' . $stepKey;

        $step->forceFill([
            'status' => 'running',
            'http_method' => 'POST',
            'graph_edge' => $this->safeEdgeForLedger($stepKey),
            'safe_request_summary' => $this->safeRequestSummary($stepKey, $payload, $origin),
            'started_at' => now(),
            'completed_at' => null,
        ])->save();

        $result = $this->providerClient->post($context['connection'], $edge, $payload, $operationKey);
        $this->recordStepResult($attempt, $step, $result, $context);

        return $result;
    }

    private function recordStepResult(FbmCampaignPublishAttempt $attempt, FbmCampaignPublishStep $step, array $result, array $context): void
    {
        if (!empty($result['successful']) && !empty($result['provider_object_id'])) {
            $localId = $this->mirrorProviderObject($step->step_key, (string) $result['provider_object_id'], $attempt, $context);
            $this->attachLocalId($attempt, (string) $step->step_key, $localId);
        }

        $step->forceFill([
            'status' => !empty($result['successful']) ? 'completed' : (!empty($result['retryable']) ? 'retryable_failed' : 'failed'),
            'http_status' => $result['http_status'] ?? null,
            'provider_response_ref' => $result['provider_response_ref'] ?? null,
            'provider_error_code' => $result['provider_error_code'] ?? null,
            'provider_error_subcode' => $result['provider_error_subcode'] ?? null,
            'redacted_message' => $this->message($result['redacted_message'] ?? null),
            'safe_response_summary' => [
                'provider_response_ref' => $result['provider_response_ref'] ?? null,
                'duration_ms' => $result['duration_ms'] ?? null,
            ],
            'completed_at' => now(),
        ])->save();
    }

    private function mirrorProviderObject(string $stepKey, string $providerObjectId, FbmCampaignPublishAttempt $attempt, array $context): int
    {
        $draft = $context['draft'];
        $base = [
            'fbm_connection_id' => (int) $context['connection']->id,
            'fbm_ad_account_id' => (int) $context['ad_account']->id,
            'provider_sync_key' => $providerObjectId,
            'is_available' => true,
            'last_seen_at' => now(),
            'updated_at' => now(),
        ];

        if ($stepKey === 'campaign') {
            $row = FbmCampaign::query()->updateOrCreate([
                'fbm_connection_id' => (int) $context['connection']->id,
                'provider_sync_key' => $providerObjectId,
            ], array_merge($base, [
                'name' => (string) $draft->draft_name,
                'objective' => (string) $draft->objective,
                'configured_status' => 'PAUSED',
                'effective_status' => 'PAUSED',
            ]));

            return (int) $row->id;
        }

        if ($stepKey === 'ad_set') {
            $row = FbmAdSet::query()->updateOrCreate([
                'fbm_connection_id' => (int) $context['connection']->id,
                'provider_sync_key' => $providerObjectId,
            ], array_merge($base, [
                'fbm_campaign_id' => (int) $attempt->fresh()->provider_campaign_local_id,
                'name' => (string) $draft->draft_name . ' Ad Set',
                'configured_status' => 'PAUSED',
                'effective_status' => 'PAUSED',
                'optimization_goal' => (string) ($draft->optimization_goal ?: 'LINK_CLICKS'),
                'billing_event' => (string) ($draft->billing_event ?: 'IMPRESSIONS'),
                'daily_budget' => $draft->budget_type === 'daily' ? (string) $this->minorUnits((float) $draft->budget_amount) : null,
                'lifetime_budget' => $draft->budget_type === 'lifetime' ? (string) $this->minorUnits((float) $draft->budget_amount) : null,
            ]));

            return (int) $row->id;
        }

        if ($stepKey === 'creative') {
            $creativeAsset = $context['creative_asset'];
            $row = FbmCreative::query()->updateOrCreate([
                'fbm_connection_id' => (int) $context['connection']->id,
                'provider_sync_key' => $providerObjectId,
            ], array_merge($base, [
                'name' => (string) ($creativeAsset->title ?? $draft->draft_name),
                'title' => (string) ($creativeAsset->headline ?: $draft->draft_name),
                'body' => (string) ($creativeAsset->primary_text ?: $draft->draft_name),
                'object_type' => 'link_data',
            ]));

            return (int) $row->id;
        }

        $row = FbmAd::query()->updateOrCreate([
            'fbm_connection_id' => (int) $context['connection']->id,
            'provider_sync_key' => $providerObjectId,
        ], array_merge($base, [
            'fbm_campaign_id' => (int) $attempt->fresh()->provider_campaign_local_id,
            'fbm_ad_set_id' => (int) $attempt->fresh()->provider_ad_set_local_id,
            'fbm_creative_id' => (int) $attempt->fresh()->provider_creative_local_id,
            'name' => (string) $draft->draft_name . ' Ad',
            'configured_status' => 'PAUSED',
            'effective_status' => 'PAUSED',
        ]));

        return (int) $row->id;
    }

    private function attachLocalId(FbmCampaignPublishAttempt $attempt, string $stepKey, int $localId): void
    {
        $column = [
            'campaign' => 'provider_campaign_local_id',
            'ad_set' => 'provider_ad_set_local_id',
            'creative' => 'provider_creative_local_id',
            'ad' => 'provider_ad_local_id',
        ][$stepKey] ?? null;

        if ($column) {
            $attempt->forceFill([$column => $localId])->save();
        }
    }

    private function payloadFor(string $stepKey, FbmCampaignPublishAttempt $attempt, array $context): array
    {
        $draft = $context['draft'];
        if ($stepKey === 'campaign') {
            return [
                'name' => $this->bounded($draft->draft_name, 255),
                'objective' => $this->bounded($draft->objective, 80),
                'status' => 'PAUSED',
                'special_ad_categories' => $this->specialCategories($draft->special_ad_categories),
            ];
        }

        if ($stepKey === 'ad_set') {
            $targeting = $this->targeting($context);
            $payload = [
                'name' => $this->bounded((string) $draft->draft_name . ' Ad Set', 255),
                'campaign_id' => $this->providerIdFor($attempt, 'campaign'),
                'status' => 'PAUSED',
                'optimization_goal' => $this->bounded($draft->optimization_goal ?: 'LINK_CLICKS', 120),
                'billing_event' => $this->bounded($draft->billing_event ?: 'IMPRESSIONS', 120),
                'targeting' => $targeting,
            ];
            if ($draft->starts_at) {
                $payload['start_time'] = $draft->starts_at->toIso8601String();
            }
            if ($draft->ends_at) {
                $payload['end_time'] = $draft->ends_at->toIso8601String();
            }
            $payload[$draft->budget_type === 'lifetime' ? 'lifetime_budget' : 'daily_budget'] = $this->minorUnits((float) $draft->budget_amount);

            return $payload;
        }

        if ($stepKey === 'creative') {
            $creativeAsset = $context['creative_asset'];
            $link = $this->destinationUrl($draft, $creativeAsset);
            $linkData = [
                'link' => $link,
                'message' => $this->bounded($creativeAsset->primary_text ?: $draft->draft_name, 1000),
                'name' => $this->bounded($creativeAsset->headline ?: $draft->draft_name, 255),
                'description' => $this->bounded($creativeAsset->description ?: $draft->draft_name, 500),
            ];
            if ($creativeAsset->call_to_action) {
                $linkData['call_to_action'] = [
                    'type' => $this->bounded($creativeAsset->call_to_action, 80),
                    'value' => ['link' => $link],
                ];
            }
            if ($creativeAsset->external_asset_url) {
                $linkData['picture'] = $this->safeUrl($creativeAsset->external_asset_url);
            }

            return [
                'name' => $this->bounded($creativeAsset->title ?: $draft->draft_name, 255),
                'object_story_spec' => [
                    'page_id' => $context['page_provider_id'],
                    'link_data' => $linkData,
                ],
            ];
        }

        return [
            'name' => $this->bounded((string) $draft->draft_name . ' Ad', 255),
            'adset_id' => $this->providerIdFor($attempt, 'ad_set'),
            'creative' => ['creative_id' => $this->providerIdFor($attempt, 'creative')],
            'status' => 'PAUSED',
        ];
    }

    private function publishContext(FbmCampaignPublishAttempt $attempt): array
    {
        $draft = $attempt->draft instanceof FbmCampaignDraft ? $attempt->draft : FbmCampaignDraft::query()->with('assets')->find($attempt->fbm_campaign_draft_id);
        if (!$draft) {
            throw new RuntimeException('Campaign draft is unavailable for the publish attempt.');
        }
        $connection = FbmConnection::query()->find((int) $draft->fbm_connection_id);
        $adAccount = FbmAdAccount::query()->find((int) $draft->fbm_ad_account_id);
        $page = FbmPage::query()->find((int) $draft->fbm_page_id);
        $creativeAssetId = optional($draft->assets->firstWhere('asset_type', 'creative_asset'))->asset_id;
        $audienceId = optional($draft->assets->firstWhere('asset_type', 'audience'))->asset_id;
        $productSetId = optional($draft->assets->firstWhere('asset_type', 'product_set'))->asset_id;
        $creativeAsset = $creativeAssetId ? FbmCreativeAsset::query()->find((int) $creativeAssetId) : null;
        $audience = $audienceId ? FbmAudience::query()->find((int) $audienceId) : null;

        return [
            'draft' => $draft,
            'connection' => $connection,
            'ad_account' => $adAccount,
            'page' => $page,
            'creative_asset' => $creativeAsset,
            'audience' => $audience,
            'product_set_id' => $productSetId ? (int) $productSetId : null,
            'ad_account_node' => $adAccount ? $this->adAccountNode((string) $adAccount->provider_asset_id) : null,
            'page_provider_id' => $page ? $this->providerId((string) $page->provider_asset_id, 'Page') : null,
        ];
    }

    private function preflight(array $context): void
    {
        $draft = $context['draft'];
        if (!$context['connection'] || !$context['ad_account'] || !$context['page']) {
            throw new RuntimeException('Campaign publish requires a connection, selected Ad Account and selected Page.');
        }
        if (!$context['ad_account']->is_available || !$context['ad_account']->is_selected) {
            throw new RuntimeException('Campaign publish requires a selected and available Ad Account.');
        }
        if (!$context['page']->is_available || !$context['page']->is_selected) {
            throw new RuntimeException('Campaign publish requires a selected and available Page.');
        }
        if (!$context['creative_asset'] || (string) $context['creative_asset']->status !== 'ready') {
            throw new RuntimeException('Campaign publish requires a ready creative asset.');
        }
        $this->destinationUrl($draft, $context['creative_asset']);
        $this->targeting($context);
        $budget = (float) $draft->budget_amount;
        $cap = $draft->budget_type === 'lifetime'
            ? (float) config('fb_marketing.provider_writer.max_single_lifetime_budget_amount', 50000)
            : (float) config('fb_marketing.provider_writer.max_single_daily_budget_amount', 5000);
        if ($budget <= 0 || $budget > $cap) {
            throw new RuntimeException('Campaign publish budget is outside the configured provider-writer safety cap.');
        }
    }

    private function targeting(array $context): array
    {
        $audience = $context['audience'];
        if ($audience && $audience->is_available && $audience->is_selected && $audience->provider_audience_id) {
            return ['custom_audiences' => [['id' => $this->providerId((string) $audience->provider_audience_id, 'Audience')]]];
        }

        $countries = $this->targetingCountries();
        if ($countries !== []) {
            return ['geo_locations' => ['countries' => $countries]];
        }

        throw new RuntimeException('Campaign publish requires a selected provider audience or configured default targeting countries.');
    }

    private function targetingCountries(): array
    {
        $countries = [];
        foreach ((array) config('fb_marketing.provider_writer.default_targeting_countries', []) as $country) {
            $country = strtoupper(trim((string) $country));
            if (preg_match('/^[A-Z]{2}$/', $country)) {
                $countries[] = $country;
            }
        }

        return array_values(array_unique($countries));
    }

    private function providerIdFor(FbmCampaignPublishAttempt $attempt, string $stepKey): string
    {
        $mapping = [
            'campaign' => [FbmCampaign::class, 'provider_campaign_local_id', 'Campaign'],
            'ad_set' => [FbmAdSet::class, 'provider_ad_set_local_id', 'Ad set'],
            'creative' => [FbmCreative::class, 'provider_creative_local_id', 'Creative'],
            'ad' => [FbmAd::class, 'provider_ad_local_id', 'Ad'],
        ][$stepKey] ?? null;
        if (!$mapping) {
            throw new RuntimeException('Unknown campaign publish dependency.');
        }

        [$class, $column, $label] = $mapping;
        $localId = (int) $attempt->fresh()->{$column};
        $row = $localId > 0 ? $class::query()->find($localId) : null;
        if (!$row || !$row->provider_sync_key) {
            throw new RuntimeException($label . ' provider dependency is unavailable for campaign publish.');
        }

        return $this->providerId((string) $row->provider_sync_key, $label);
    }

    private function edgeFor(string $stepKey, string $adAccountNode): string
    {
        return $adAccountNode . '/' . [
            'campaign' => 'campaigns',
            'ad_set' => 'adsets',
            'creative' => 'adcreatives',
            'ad' => 'ads',
        ][$stepKey];
    }

    private function safeEdgeForLedger(string $stepKey): string
    {
        return 'act_{ad_account_id}/' . [
            'campaign' => 'campaigns',
            'ad_set' => 'adsets',
            'creative' => 'adcreatives',
            'ad' => 'ads',
        ][$stepKey];
    }

    private function adAccountNode(string $providerId): string
    {
        $providerId = trim($providerId);
        if (preg_match('/^act_[0-9A-Za-z_.:-]+$/', $providerId)) {
            return $providerId;
        }

        return 'act_' . $this->providerId($providerId, 'Ad Account');
    }

    private function providerId(string $value, string $label): string
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 190 || !preg_match('/^[A-Za-z0-9_.:-]+$/', $value)) {
            throw new RuntimeException($label . ' provider identifier is not available for campaign publish.');
        }

        return $value;
    }

    private function destinationUrl(FbmCampaignDraft $draft, FbmCreativeAsset $creativeAsset): string
    {
        return $this->safeUrl($draft->destination_url ?: $creativeAsset->landing_url);
    }

    private function safeUrl($value): string
    {
        $value = trim((string) $value);
        $parts = parse_url($value);
        if (!is_array($parts)
            || !in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            || trim((string) ($parts['host'] ?? '')) === ''
            || strlen($value) > 1000) {
            throw new RuntimeException('Campaign publish requires a valid absolute HTTP or HTTPS destination URL.');
        }

        return $value;
    }

    private function specialCategories($value): array
    {
        $values = is_array($value) ? $value : [];
        $safe = [];
        foreach ($values as $item) {
            $item = strtoupper(trim((string) $item));
            if (in_array($item, ['NONE', 'CREDIT', 'EMPLOYMENT', 'HOUSING', 'ISSUES_ELECTIONS_POLITICS'], true)) {
                $safe[] = $item;
            }
        }

        return $safe === [] ? ['NONE'] : array_values(array_unique($safe));
    }

    private function minorUnits(float $amount): int
    {
        return max(1, (int) round($amount * 100));
    }

    private function bounded($value, int $length): string
    {
        $value = trim(SecretRedactor::redactString((string) $value));
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? '';
        if ($value === '') {
            throw new RuntimeException('Campaign publish payload is missing a required bounded value.');
        }

        return substr($value, 0, $length);
    }

    private function safeRequestSummary(string $stepKey, array $payload, string $origin): array
    {
        return [
            'step_key' => $stepKey,
            'origin' => $origin,
            'payload_keys' => array_values(array_keys($payload)),
            'status' => $payload['status'] ?? null,
            'budget_field' => array_key_exists('daily_budget', $payload) ? 'daily_budget' : (array_key_exists('lifetime_budget', $payload) ? 'lifetime_budget' : null),
        ];
    }

    private function blockAttempt(FbmCampaignPublishAttempt $attempt, string $reason, string $message): FbmCampaignPublishAttempt
    {
        $message = $this->message($message);
        $attempt->steps()->whereIn('status', ['pending', 'running'])->update([
            'status' => 'blocked_preflight',
            'redacted_message' => $message,
            'completed_at' => now(),
        ]);
        $attempt->forceFill([
            'status' => 'blocked_preflight',
            'safe_response_summary' => ['provider_request_count' => 0, 'blocked_reason' => $reason],
            'redacted_message' => $message,
            'completed_at' => now(),
        ])->save();

        return $attempt->fresh();
    }

    private function failAttempt(FbmCampaignPublishAttempt $attempt, ?FbmCampaignPublishStep $step, string $status, string $message, bool $retryable, int $providerRequestCount = 0): FbmCampaignPublishAttempt
    {
        $message = $this->message($message);
        if ($step && (string) $step->status === 'running') {
            $step->forceFill([
                'status' => $retryable ? 'retryable_failed' : 'failed',
                'redacted_message' => $message,
                'completed_at' => now(),
            ])->save();
        }

        $attempt->forceFill([
            'status' => $status,
            'safe_response_summary' => [
                'provider_request_count' => $providerRequestCount,
                'retryable' => $retryable,
                'rollback_plan' => $this->writeSafety->publishRollbackPlan($attempt->fresh(['steps'])),
                'reconciliation' => $this->writeSafety->publishReconciliationSummary($attempt),
            ],
            'redacted_message' => $message,
            'completed_at' => $retryable ? null : now(),
        ])->save();

        return $attempt->fresh();
    }

    private function message($message): string
    {
        $message = trim(SecretRedactor::redactString((string) $message));

        return $message !== '' ? substr($message, 0, 500) : 'Campaign publish stopped safely.';
    }

    private function attempt(string $attemptUuid): FbmCampaignPublishAttempt
    {
        if (!$this->schemaReady()) {
            throw new RuntimeException('FB MARKETING campaign publish schema is not ready.');
        }

        return FbmCampaignPublishAttempt::query()
            ->with(['draft.assets', 'snapshot', 'steps'])
            ->where('attempt_uuid', $this->normalizeUuid($attemptUuid))
            ->firstOrFail();
    }

    private function normalizeUuid(string $uuid): string
    {
        $uuid = trim($uuid);
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $uuid)) {
            throw new RuntimeException('FB MARKETING received an invalid campaign publish attempt reference.');
        }

        return $uuid;
    }

    private function schemaReady(): bool
    {
        foreach (['fbm_campaign_publish_attempts', 'fbm_campaign_publish_steps', 'fbm_campaign_drafts', 'fbm_campaigns', 'fbm_ad_sets', 'fbm_creatives', 'fbm_ads'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function lockSeconds(): int
    {
        return max(30, min(1200, (int) config('fb_marketing.campaign_publish.lock_ttl_seconds', 300)));
    }

    private function hmac(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key', ''));
    }
}
