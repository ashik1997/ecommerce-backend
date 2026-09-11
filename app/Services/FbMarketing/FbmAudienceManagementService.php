<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAdAccount;
use App\Models\FbMarketing\FbmAudience;
use App\Models\FbMarketing\FbmAudienceSelectionAudit;
use App\Models\FbMarketing\FbmAudienceSyncRun;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmProductSet;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FbmAudienceManagementService
{
    protected FbmGraphClient $graphClient;

    public function __construct(FbmGraphClient $graphClient)
    {
        $this->graphClient = $graphClient;
    }

    public function build(array $filters): array
    {
        $schemaReady = $this->schemaReady();
        $safeFilters = $this->safeFilters($filters);
        $warnings = [];

        if (!$schemaReady) {
            $warnings[] = $this->warning('danger', 'FBM-22 audience management tables are required before audiences are available.');
        }

        $audiences = $schemaReady ? $this->audiences($safeFilters) : collect();
        $productSets = $schemaReady ? $this->productSets() : collect();

        return [
            'schema_ready' => $schemaReady,
            'filters' => $safeFilters,
            'audience_type_options' => $this->audienceTypeOptions(),
            'status_options' => $this->statusOptions(),
            'summary' => $this->summary($audiences, $productSets),
            'audiences' => $audiences->values()->all(),
            'product_sets' => $productSets->values()->all(),
            'sync_runs' => $schemaReady ? $this->syncRuns()->values()->all() : [],
            'audits' => $schemaReady ? $this->audits()->values()->all() : [],
            'connections' => $schemaReady ? $this->syncableConnections()->values()->all() : [],
            'warnings' => $warnings,
        ];
    }

    public function storeAudience(array $input, ?int $userId): void
    {
        if (!$this->schemaReady()) {
            return;
        }

        FbmAudience::query()->create([
            'audience_uuid' => (string) Str::uuid(),
            'audience_type' => $this->safeOption($input['audience_type'] ?? 'saved', $this->audienceTypeOptions(), 'saved'),
            'audience_name' => $this->safeString($input['audience_name'] ?? 'Audience', 255) ?: 'Audience',
            'subtype' => $this->safeString($input['subtype'] ?? null, 80),
            'description' => $this->safeString($input['description'] ?? null, 500),
            'source_summary' => ['origin' => 'local_planning'],
            'approximate_count' => $this->safeInteger($input['approximate_count'] ?? null),
            'status' => $this->safeOption($input['status'] ?? 'draft', $this->statusOptions(), 'draft'),
            'planned_use' => $this->safeString($input['planned_use'] ?? null, 120),
            'consent_basis' => $this->safeString($input['consent_basis'] ?? null, 120),
            'consent_note' => $this->safeString($input['consent_note'] ?? null, 500),
            'retention_days' => $this->safeInteger($input['retention_days'] ?? null),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function updateAudienceSelection(int $audienceId, array $input, ?int $userId): void
    {
        if (!$this->schemaReady()) {
            return;
        }

        $audience = FbmAudience::query()->find($audienceId);
        if (!$audience) {
            return;
        }

        $before = $this->selectionState($audience);
        $selected = $this->booleanValue($input['is_selected'] ?? null, (bool) $audience->is_selected);

        $audience->forceFill([
            'is_selected' => $selected,
            'planned_use' => $this->safeString($input['planned_use'] ?? $audience->planned_use, 120),
            'consent_note' => $this->safeString($input['consent_note'] ?? $audience->consent_note, 500),
            'selected_by' => $selected ? $userId : null,
            'selected_at' => $selected ? now() : null,
            'updated_by' => $userId,
        ])->save();

        $this->audit('audience', (int) $audience->id, $selected ? 'selected' : 'deselected', $before, $this->selectionState($audience), $userId, $input['reason'] ?? null);
    }

    public function updateProductSetSelection(int $productSetId, array $input, ?int $userId): void
    {
        if (!$this->schemaReady()) {
            return;
        }

        $productSet = FbmProductSet::query()->find($productSetId);
        if (!$productSet) {
            return;
        }

        $before = $this->selectionState($productSet);
        $selected = $this->booleanValue($input['is_selected'] ?? null, (bool) $productSet->is_selected);

        $productSet->forceFill([
            'is_selected' => $selected,
            'selection_status' => $selected ? 'selected' : 'available',
            'planned_use' => $this->safeString($input['planned_use'] ?? $productSet->planned_use, 120),
            'consent_note' => $this->safeString($input['consent_note'] ?? $productSet->consent_note, 500),
            'selected_by' => $selected ? $userId : null,
            'selected_at' => $selected ? now() : null,
        ])->save();

        $this->audit('product_set', (int) $productSet->id, $selected ? 'selected' : 'deselected', $before, $this->selectionState($productSet), $userId, $input['reason'] ?? null);
    }

    public function syncAudiences(FbmConnection $connection, ?User $user): array
    {
        if (!$this->schemaReady()) {
            return ['status' => 'skipped'];
        }

        $startedAt = now();
        $warnings = [];
        $savedCount = 0;
        $customCount = 0;
        $status = 'success';
        $message = 'Read-only audience sync completed.';
        $adAccounts = FbmAdAccount::query()
            ->where('fbm_connection_id', (int) $connection->id)
            ->where('is_available', true)
            ->where('is_selected', true)
            ->orderBy('asset_name')
            ->limit($this->maxAdAccountsPerSync())
            ->get();

        if ($adAccounts->isEmpty()) {
            $status = 'skipped';
            $message = 'No selected ad account was available for read-only audience sync.';
            $warnings[] = $message;
        }

        foreach ($adAccounts as $adAccount) {
            foreach (['saved' => 'saved_audiences', 'custom' => 'customaudiences'] as $type => $edge) {
                $result = $this->graphClient->paginateEdge(
                    $connection,
                    trim((string) $adAccount->provider_asset_id) . '/' . $edge,
                    $this->audienceFields($type),
                    null,
                    'audience_' . $type . '_sync',
                    'audience_sync'
                );

                if (empty($result['success'])) {
                    $status = $status === 'success' ? 'partial_success' : $status;
                    $warnings[] = $this->safeString($result['redacted_message'] ?? 'Audience edge sync returned a safe warning.', 500);
                    continue;
                }

                foreach (($result['items'] ?? []) as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $this->upsertSyncedAudience($connection, $adAccount, $type, $row);
                    $type === 'saved' ? $savedCount++ : $customCount++;
                }
            }
        }

        FbmAudienceSyncRun::query()->create([
            'fbm_connection_id' => (int) $connection->id,
            'fbm_ad_account_id' => optional($adAccounts->first())->id,
            'actor_user_id' => optional($user)->id,
            'execution_mode' => 'manual',
            'status' => $status,
            'graph_api_version' => $connection->graph_api_version,
            'saved_audience_count' => $savedCount,
            'custom_audience_count' => $customCount,
            'warnings' => array_values(array_filter($warnings)),
            'redacted_message' => $message,
            'started_at' => $startedAt,
            'completed_at' => now(),
        ]);

        return ['status' => $status, 'message' => $message];
    }

    private function upsertSyncedAudience(FbmConnection $connection, FbmAdAccount $adAccount, string $type, array $row): void
    {
        $providerId = $this->safeString($row['id'] ?? null, 190);
        if (!$providerId) {
            return;
        }

        $audience = FbmAudience::query()->firstOrNew([
            'fbm_ad_account_id' => (int) $adAccount->id,
            'provider_audience_id' => $providerId,
        ]);

        $audience->forceFill([
            'audience_uuid' => $audience->audience_uuid ?: (string) Str::uuid(),
            'fbm_connection_id' => (int) $connection->id,
            'audience_type' => $type,
            'audience_name' => $this->safeString($row['name'] ?? 'Meta audience', 255) ?: 'Meta audience',
            'subtype' => $this->safeString($row['subtype'] ?? null, 80),
            'description' => $this->safeString($row['description'] ?? null, 500),
            'source_summary' => [
                'origin' => 'meta_read_only_sync',
                'delivery_status' => $this->safeString($row['delivery_status']['code'] ?? null, 80),
                'operation_status' => $this->safeString($row['operation_status']['code'] ?? null, 80),
            ],
            'approximate_count' => $this->safeInteger($row['approximate_count'] ?? null),
            'status' => 'ready',
            'is_available' => true,
            'last_synced_at' => now(),
        ])->save();
    }

    private function audiences(array $filters): Collection
    {
        $query = FbmAudience::query()
            ->with(['connection:id,connection_name', 'adAccount:id,asset_name'])
            ->orderByDesc('is_selected')
            ->orderBy('audience_type')
            ->orderBy('audience_name')
            ->limit($this->maxRows());

        if ($filters['audience_type'] !== null) {
            $query->where('audience_type', $filters['audience_type']);
        }
        if ($filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }
        if ($filters['selection'] === 'selected') {
            $query->where('is_selected', true);
        } elseif ($filters['selection'] === 'unselected') {
            $query->where('is_selected', false);
        }

        return $query->get()->map(fn(FbmAudience $audience): array => $audience->toSafeSummary());
    }

    private function productSets(): Collection
    {
        if (!Schema::hasTable('fbm_product_sets')) {
            return collect();
        }

        return FbmProductSet::query()
            ->with('catalog:id,asset_name')
            ->orderByDesc('is_selected')
            ->orderBy('set_name')
            ->limit($this->maxRows())
            ->get()
            ->map(fn(FbmProductSet $set): array => $set->toSafeSummary());
    }

    private function syncRuns(): Collection
    {
        return FbmAudienceSyncRun::query()
            ->with(['connection:id,connection_name', 'adAccount:id,asset_name'])
            ->orderByDesc('created_at')
            ->limit(max(1, (int) config('fb_marketing.audiences.sync_history_limit', 20)))
            ->get()
            ->map(fn(FbmAudienceSyncRun $run): array => $run->toSafeSummary());
    }

    private function audits(): Collection
    {
        return FbmAudienceSelectionAudit::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(max(1, (int) config('fb_marketing.audiences.audit_history_limit', 30)))
            ->get()
            ->map(fn(FbmAudienceSelectionAudit $audit): array => $audit->toSafeSummary());
    }

    private function syncableConnections(): Collection
    {
        return FbmConnection::query()
            ->where('is_active', true)
            ->orderBy('connection_name')
            ->get(['id', 'connection_name'])
            ->filter(fn(FbmConnection $connection): bool => FbmAdAccount::query()
                ->where('fbm_connection_id', (int) $connection->id)
                ->where('is_available', true)
                ->where('is_selected', true)
                ->exists())
            ->map(fn(FbmConnection $connection): array => [
                'id' => (int) $connection->id,
                'connection_name' => (string) $connection->connection_name,
            ]);
    }

    private function summary(Collection $audiences, Collection $productSets): array
    {
        return [
            'audience_count' => $audiences->count(),
            'selected_audience_count' => $audiences->where('is_selected', true)->count(),
            'custom_audience_count' => $audiences->where('audience_type', 'custom')->count(),
            'product_set_count' => $productSets->count(),
            'selected_product_set_count' => $productSets->where('is_selected', true)->count(),
        ];
    }

    private function selectionState($model): array
    {
        return [
            'is_selected' => (bool) $model->is_selected,
            'planned_use' => (string) $model->planned_use,
            'consent_note_configured' => trim((string) $model->consent_note) !== '',
        ];
    }

    private function audit(string $objectType, int $objectId, string $action, array $before, array $after, ?int $userId, $reason): void
    {
        FbmAudienceSelectionAudit::query()->create([
            'object_type' => $objectType,
            'object_id' => $objectId,
            'action' => $action,
            'before_state' => $before,
            'after_state' => $after,
            'actor_user_id' => $userId,
            'reason' => $this->safeString($reason, 500),
            'created_at' => now(),
        ]);
    }

    private function schemaReady(): bool
    {
        foreach (['fbm_audiences', 'fbm_audience_sync_runs', 'fbm_audience_selection_audits', 'fbm_product_sets'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return Schema::hasColumn('fbm_product_sets', 'is_selected');
    }

    private function safeFilters(array $filters): array
    {
        return [
            'audience_type' => array_key_exists((string) ($filters['audience_type'] ?? ''), $this->audienceTypeOptions()) ? (string) $filters['audience_type'] : null,
            'status' => array_key_exists((string) ($filters['status'] ?? ''), $this->statusOptions()) ? (string) $filters['status'] : null,
            'selection' => in_array((string) ($filters['selection'] ?? ''), ['selected', 'unselected'], true) ? (string) $filters['selection'] : null,
        ];
    }

    private function audienceFields(string $type): array
    {
        return $type === 'custom'
            ? ['id', 'name', 'subtype', 'description', 'approximate_count', 'delivery_status', 'operation_status']
            : ['id', 'name', 'subtype', 'description', 'approximate_count'];
    }

    private function audienceTypeOptions(): array
    {
        return ['saved' => 'Saved', 'custom' => 'Custom', 'lookalike' => 'Lookalike', 'local_segment' => 'Local segment'];
    }

    private function statusOptions(): array
    {
        return ['draft' => 'Draft', 'ready' => 'Ready', 'paused' => 'Paused', 'archived' => 'Archived'];
    }

    private function safeOption($value, array $options, string $fallback): string
    {
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';

        return array_key_exists($value, $options) ? $value : $fallback;
    }

    private function safeString($value, int $length): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : substr($value, 0, $length);
    }

    private function safeInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = (int) $value;

        return $value < 0 ? null : $value;
    }

    private function booleanValue($value, bool $fallback): bool
    {
        if ($value === null) {
            return $fallback;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function maxRows(): int
    {
        return max(100, min(5000, (int) config('fb_marketing.audiences.max_rows', 1000)));
    }

    private function maxAdAccountsPerSync(): int
    {
        return max(1, min(25, (int) config('fb_marketing.audiences.max_ad_accounts_per_sync', 10)));
    }

    private function warning(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }
}
