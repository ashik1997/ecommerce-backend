<?php

namespace App\Services\FbMarketing;

use App\Models\Crm\CrmLead;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmLeadAdEvent;
use App\Models\FbMarketing\FbmLeadAdWebhookLog;
use App\Models\FbMarketing\FbmPage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FbmLeadAdsService
{
    public function build(): array
    {
        $schemaReady = $this->schemaReady();
        $crmReady = Schema::hasTable('crm_leads');

        return [
            'schema_ready' => $schemaReady,
            'crm_ready' => $crmReady,
            'webhook_path' => '/api/fb-marketing/webhooks/lead-ads',
            'summary' => $schemaReady ? $this->summary() : $this->emptySummary(),
            'events' => $schemaReady ? $this->events()->values()->all() : [],
            'webhook_logs' => $schemaReady ? $this->webhookLogs()->values()->all() : [],
            'warnings' => $schemaReady ? [] : [$this->warning('danger', 'FBM-26 Lead Ads tables are required before lead ingestion is available.')],
        ];
    }

    public function verify(Request $request): array
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));
        $connection = $this->connectionForVerifyToken($token);

        if ($mode === 'subscribe' && $connection && $challenge !== '') {
            $this->log('verify', 'success', $connection, $request, 'Lead Ads webhook verification succeeded.');
            return ['ok' => true, 'challenge' => $challenge];
        }

        $this->log('verify', 'failed', $connection, $request, 'Lead Ads webhook verification failed safely.');
        return ['ok' => false, 'challenge' => null];
    }

    public function receive(Request $request): array
    {
        if (!$this->schemaReady()) {
            return ['status' => 'schema_not_ready', 'processed' => 0];
        }

        $payload = $request->all();
        $processed = 0;

        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = is_array($change['value'] ?? null) ? $change['value'] : [];
                if (($change['field'] ?? '') !== 'leadgen' && empty($value['leadgen_id'])) {
                    continue;
                }

                $this->persistLeadValue($value, $request);
                $processed++;
            }
        }

        if ($processed === 0 && is_array($payload['value'] ?? null)) {
            $this->persistLeadValue($payload['value'], $request);
            $processed = 1;
        }

        $this->log('callback', $processed > 0 ? 'received' : 'ignored', null, $request, $processed > 0 ? 'Lead Ads webhook callback received.' : 'Lead Ads webhook contained no leadgen change.');

        return ['status' => 'ok', 'processed' => $processed];
    }

    private function persistLeadValue(array $value, Request $request): void
    {
        $providerLeadId = $this->safeProviderId($value['leadgen_id'] ?? $value['id'] ?? null);
        $fingerprint = $this->fingerprint($request, $providerLeadId ?: json_encode($this->safeValueShape($value)));
        $connection = $this->connectionForPageId($value['page_id'] ?? null);
        $page = $this->pageForProviderId($value['page_id'] ?? null);
        $fieldData = $this->fieldData($value);
        $fieldKeys = array_keys($fieldData);

        DB::transaction(function () use ($value, $providerLeadId, $fingerprint, $connection, $page, $fieldData, $fieldKeys): void {
            $event = $providerLeadId
                ? FbmLeadAdEvent::query()->firstOrNew(['provider_leadgen_id' => $providerLeadId])
                : new FbmLeadAdEvent();

            if (!$event->exists) {
                $event->event_uuid = (string) Str::uuid();
            }

            $event->forceFill([
                'fbm_connection_id' => optional($connection)->id,
                'fbm_page_id' => optional($page)->id,
                'provider_leadgen_id' => $providerLeadId,
                'provider_form_id' => $this->safeProviderId($value['form_id'] ?? null),
                'provider_page_id' => $this->safeProviderId($value['page_id'] ?? null),
                'provider_ad_id' => $this->safeProviderId($value['ad_id'] ?? null),
                'provider_adgroup_id' => $this->safeProviderId($value['adgroup_id'] ?? null),
                'field_keys' => $fieldKeys,
                'mapped_field_flags' => $this->mappedFlags($fieldData),
                'request_fingerprint' => $fingerprint,
                'received_at' => $event->received_at ?: now(),
            ]);

            if ($event->crm_lead_id) {
                $event->status = 'duplicate_ignored';
                $event->redacted_message = 'Duplicate Lead Ads event ignored; CRM lead already exists.';
                $event->save();
                return;
            }

            if (empty($fieldData)) {
                $event->status = 'pending_retrieval';
                $event->redacted_message = 'Leadgen ID received without field data. Safe retrieval remains pending.';
                $event->save();
                return;
            }

            $lead = $this->createCrmLead($fieldData);
            $event->crm_lead_id = optional($lead)->id;
            $event->status = $lead ? 'mapped_to_crm' : 'crm_unavailable';
            $event->mapped_at = $lead ? now() : null;
            $event->redacted_message = $lead ? 'Lead Ads event created one CRM lead.' : 'CRM lead table is unavailable.';
            $event->save();
        });
    }

    private function createCrmLead(array $fieldData): ?CrmLead
    {
        if (!Schema::hasTable('crm_leads')) {
            return null;
        }

        $name = $this->firstValue($fieldData, ['full_name', 'name', 'first_name']) ?: 'Facebook Lead';
        $requirementParts = [];
        foreach ($fieldData as $key => $value) {
            if (in_array($key, ['full_name', 'name', 'first_name', 'last_name', 'phone_number', 'phone', 'email', 'company_name'], true)) {
                continue;
            }
            $requirementParts[] = $key . ': ' . $value;
        }

        return CrmLead::query()->create([
            'name' => substr($name, 0, 255),
            'company_name' => $this->firstValue($fieldData, ['company_name', 'company']),
            'phone' => $this->firstValue($fieldData, ['phone_number', 'phone']),
            'email' => filter_var($this->firstValue($fieldData, ['email']), FILTER_VALIDATE_EMAIL) ? $this->firstValue($fieldData, ['email']) : null,
            'source' => 'facebook',
            'status' => 'new',
            'priority' => 'normal',
            'score' => 0,
            'requirement' => substr(implode("\n", $requirementParts), 0, 5000),
        ]);
    }

    private function connectionForVerifyToken(string $token): ?FbmConnection
    {
        if ($token === '' || !Schema::hasTable('fbm_connections')) {
            return null;
        }

        return FbmConnection::query()->where('is_active', true)->get()->first(function (FbmConnection $connection) use ($token): bool {
            $configured = (string) $connection->webhook_verify_token_ciphertext;
            return $configured !== '' && hash_equals($configured, $token);
        });
    }

    private function connectionForPageId($providerPageId): ?FbmConnection
    {
        $page = $this->pageForProviderId($providerPageId);
        return $page ? $page->connection : null;
    }

    private function pageForProviderId($providerPageId): ?FbmPage
    {
        $providerPageId = $this->safeProviderId($providerPageId);
        if (!$providerPageId || !Schema::hasTable('fbm_pages')) {
            return null;
        }

        return FbmPage::query()->where('provider_asset_id', $providerPageId)->first();
    }

    private function fieldData(array $value): array
    {
        $rows = (array) ($value['field_data'] ?? $value['fieldData'] ?? []);
        $mapped = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = strtolower(trim((string) ($row['name'] ?? '')));
            $values = (array) ($row['values'] ?? []);
            $first = $values[0] ?? null;
            if ($key !== '' && is_scalar($first)) {
                $mapped[preg_replace('/[^a-z0-9_]/', '_', $key)] = substr(trim((string) $first), 0, 500);
            }
        }

        foreach (['full_name', 'name', 'phone_number', 'phone', 'email', 'company_name'] as $key) {
            if (!isset($mapped[$key]) && isset($value[$key]) && is_scalar($value[$key])) {
                $mapped[$key] = substr(trim((string) $value[$key]), 0, 500);
            }
        }

        return array_filter($mapped, fn($value) => $value !== '');
    }

    private function mappedFlags(array $fieldData): array
    {
        return [
            'has_name' => $this->firstValue($fieldData, ['full_name', 'name', 'first_name']) !== null,
            'has_phone' => $this->firstValue($fieldData, ['phone_number', 'phone']) !== null,
            'has_email' => $this->firstValue($fieldData, ['email']) !== null,
            'custom_field_count' => max(0, count($fieldData) - 4),
        ];
    }

    private function firstValue(array $fieldData, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!empty($fieldData[$key])) {
                return (string) $fieldData[$key];
            }
        }

        return null;
    }

    private function summary(): array
    {
        return [
            'event_count' => FbmLeadAdEvent::query()->count(),
            'mapped_count' => FbmLeadAdEvent::query()->where('status', 'mapped_to_crm')->count(),
            'pending_count' => FbmLeadAdEvent::query()->where('status', 'pending_retrieval')->count(),
            'duplicate_count' => FbmLeadAdEvent::query()->where('status', 'duplicate_ignored')->count(),
        ];
    }

    private function emptySummary(): array
    {
        return ['event_count' => 0, 'mapped_count' => 0, 'pending_count' => 0, 'duplicate_count' => 0];
    }

    private function events(): Collection
    {
        return FbmLeadAdEvent::query()
            ->with('connection:id,connection_name')
            ->orderByDesc('received_at')
            ->limit(max(1, (int) config('fb_marketing.lead_ads.event_history_limit', 40)))
            ->get()
            ->map(fn(FbmLeadAdEvent $event): array => $event->toSafeSummary());
    }

    private function webhookLogs(): Collection
    {
        return FbmLeadAdWebhookLog::query()
            ->with('connection:id,connection_name')
            ->orderByDesc('created_at')
            ->limit(max(1, (int) config('fb_marketing.lead_ads.webhook_history_limit', 30)))
            ->get()
            ->map(fn(FbmLeadAdWebhookLog $log): array => $log->toSafeSummary());
    }

    private function log(string $eventType, string $status, ?FbmConnection $connection, Request $request, string $message): void
    {
        if (!Schema::hasTable('fbm_lead_ad_webhook_logs')) {
            return;
        }

        FbmLeadAdWebhookLog::query()->create([
            'fbm_connection_id' => optional($connection)->id,
            'event_type' => $eventType,
            'status' => $status,
            'request_fingerprint' => $this->fingerprint($request, $eventType . '|' . $status),
            'redacted_message' => $message,
            'created_at' => now(),
        ]);
    }

    private function safeProviderId($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value !== '' && preg_match('/^[A-Za-z0-9_.:-]+$/', $value) ? substr($value, 0, 190) : null;
    }

    private function safeValueShape(array $value): array
    {
        return [
            'keys' => array_values(array_filter(array_keys($value), 'is_string')),
            'has_field_data' => !empty($value['field_data']) || !empty($value['fieldData']),
        ];
    }

    private function fingerprint(Request $request, string $suffix): string
    {
        return hash_hmac('sha256', implode('|', [
            'fbm-lead-ads',
            (string) $request->ip(),
            substr((string) $request->userAgent(), 0, 120),
            $suffix,
        ]), (string) config('app.key', ''));
    }

    private function schemaReady(): bool
    {
        return Schema::hasTable('fbm_lead_ad_events') && Schema::hasTable('fbm_lead_ad_webhook_logs');
    }

    private function warning(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }
}
