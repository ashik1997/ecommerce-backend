<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAdAccount;
use App\Models\FbMarketing\FbmAudience;
use App\Models\FbMarketing\FbmCampaignDraft;
use App\Models\FbMarketing\FbmCampaignDraftApproval;
use App\Models\FbMarketing\FbmCampaignDraftAsset;
use App\Models\FbMarketing\FbmCampaignPublishSnapshot;
use App\Models\FbMarketing\FbmConnection;
use App\Models\FbMarketing\FbmCreativeAsset;
use App\Models\FbMarketing\FbmPage;
use App\Models\FbMarketing\FbmProductSet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FbmCampaignDraftPlannerService
{
    public function build(array $filters): array
    {
        $schemaReady = $this->schemaReady();
        $safeFilters = [
            'status' => in_array((string) ($filters['status'] ?? ''), array_keys($this->statusOptions()), true) ? (string) $filters['status'] : null,
        ];
        $drafts = $schemaReady ? $this->drafts($safeFilters) : collect();

        return [
            'schema_ready' => $schemaReady,
            'filters' => $safeFilters,
            'status_options' => $this->statusOptions(),
            'objective_options' => $this->objectiveOptions(),
            'special_category_options' => $this->specialCategoryOptions(),
            'summary' => $this->summary($drafts),
            'drafts' => $drafts->values()->all(),
            'approvals' => $schemaReady ? $this->approvals()->values()->all() : [],
            'connections' => $schemaReady ? $this->connections()->values()->all() : [],
            'ad_accounts' => $schemaReady ? $this->adAccounts()->values()->all() : [],
            'pages' => $schemaReady ? $this->pages()->values()->all() : [],
            'creative_assets' => $schemaReady ? $this->creativeAssets()->values()->all() : [],
            'audiences' => $schemaReady ? $this->audiences()->values()->all() : [],
            'product_sets' => $schemaReady ? $this->productSets()->values()->all() : [],
            'warnings' => $schemaReady ? [] : [$this->warning('danger', 'FBM-23 campaign draft tables are required before draft planning is available.')],
        ];
    }

    public function storeDraft(array $input, ?int $userId): void
    {
        if (!$this->schemaReady()) {
            return;
        }

        DB::transaction(function () use ($input, $userId): void {
            $draft = FbmCampaignDraft::query()->create([
                'draft_uuid' => (string) Str::uuid(),
                'fbm_connection_id' => $this->existingId('fbm_connections', $input['fbm_connection_id'] ?? null),
                'fbm_ad_account_id' => $this->existingId('fbm_ad_accounts', $input['fbm_ad_account_id'] ?? null),
                'fbm_page_id' => $this->existingId('fbm_pages', $input['fbm_page_id'] ?? null),
                'draft_name' => $this->safeString($input['draft_name'] ?? 'Campaign draft', 255) ?: 'Campaign draft',
                'objective' => $this->safeOption($input['objective'] ?? 'OUTCOME_SALES', $this->objectiveOptions(), 'OUTCOME_SALES'),
                'special_ad_categories' => $this->safeSpecialCategories($input['special_ad_categories'] ?? []),
                'budget_type' => $this->safeOption($input['budget_type'] ?? 'daily', ['daily' => 'Daily', 'lifetime' => 'Lifetime'], 'daily'),
                'budget_amount' => $this->money($input['budget_amount'] ?? 0),
                'currency' => $this->safeCurrency($input['currency'] ?? null),
                'starts_at' => $this->dateValue($input['starts_at'] ?? null),
                'ends_at' => $this->dateValue($input['ends_at'] ?? null),
                'optimization_goal' => $this->safeString($input['optimization_goal'] ?? null, 120),
                'billing_event' => $this->safeString($input['billing_event'] ?? null, 120),
                'destination_url' => $this->safeUrl($input['destination_url'] ?? null),
                'utm_source' => $this->safeCode($input['utm_source'] ?? null, 80),
                'utm_medium' => $this->safeCode($input['utm_medium'] ?? null, 80),
                'utm_campaign' => $this->safeCode($input['utm_campaign'] ?? null, 160),
                'notes' => $this->safeString($input['notes'] ?? null, 2000),
                'status' => 'draft',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->attachAsset($draft, 'creative_asset', $input['creative_asset_id'] ?? null, 'primary');
            $this->attachAsset($draft, 'audience', $input['audience_id'] ?? null, 'targeting');
            $this->attachAsset($draft, 'product_set', $input['product_set_id'] ?? null, 'catalog');
            $this->recordApproval($draft, 'created', null, 'draft', $userId, 'Draft created locally.', ['asset_count' => $draft->assets()->count()]);
        });
    }

    public function submit(int $draftId, ?int $userId, ?string $comment): void
    {
        $draft = $this->mutableDraft($draftId);
        if (!$draft || $draft->status !== 'draft') {
            return;
        }

        $from = $draft->status;
        $draft->forceFill(['status' => 'submitted', 'submitted_by' => $userId, 'submitted_at' => now(), 'updated_by' => $userId])->save();
        $this->recordApproval($draft, 'submitted', $from, 'submitted', $userId, $comment, $this->readiness($draft));
    }

    public function approve(int $draftId, ?int $userId, ?string $comment): void
    {
        $draft = FbmCampaignDraft::query()->with('assets')->find($draftId);
        if (!$draft || $draft->status !== 'submitted') {
            return;
        }

        DB::transaction(function () use ($draft, $userId, $comment): void {
            $from = $draft->status;
            $version = (int) $draft->approval_version + 1;
            $draft->forceFill([
                'status' => 'approved',
                'approval_version' => $version,
                'approved_by' => $userId,
                'approved_at' => now(),
                'updated_by' => $userId,
            ])->save();

            $payload = $this->publishSnapshotPayload($draft->fresh(['connection', 'adAccount', 'page', 'assets']));
            FbmCampaignPublishSnapshot::query()->firstOrCreate([
                'fbm_campaign_draft_id' => (int) $draft->id,
                'approval_version' => $version,
            ], [
                'snapshot_uuid' => (string) Str::uuid(),
                'snapshot_payload' => $payload,
                'payload_hash' => hash('sha256', json_encode($payload)),
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $this->recordApproval($draft, 'approved', $from, 'approved', $userId, $comment, ['approval_version' => $version, 'snapshot_created' => true]);
        });
    }

    public function reject(int $draftId, ?int $userId, ?string $comment): void
    {
        $draft = FbmCampaignDraft::query()->find($draftId);
        if (!$draft || $draft->status !== 'submitted') {
            return;
        }

        $from = $draft->status;
        $draft->forceFill(['status' => 'rejected', 'rejected_by' => $userId, 'rejected_at' => now(), 'updated_by' => $userId])->save();
        $this->recordApproval($draft, 'rejected', $from, 'rejected', $userId, $comment, ['approval_version' => (int) $draft->approval_version]);
    }

    private function drafts(array $filters): Collection
    {
        $query = FbmCampaignDraft::query()
            ->with(['connection:id,connection_name', 'adAccount:id,asset_name', 'page:id,asset_name', 'assets'])
            ->orderByDesc('created_at')
            ->limit($this->maxRows());

        if ($filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        return $query->get()->map(function (FbmCampaignDraft $draft): array {
            $row = $draft->toSafeSummary();
            $row['asset_counts'] = $draft->assets->groupBy('asset_type')->map->count()->all();
            $row['can_submit'] = $draft->status === 'draft';
            $row['can_approve'] = $draft->status === 'submitted';
            $row['can_reject'] = $draft->status === 'submitted';

            return $row;
        });
    }

    private function attachAsset(FbmCampaignDraft $draft, string $type, $id, string $role): void
    {
        $id = (int) $id;
        if ($id <= 0) {
            return;
        }

        $snapshot = $this->assetSnapshot($type, $id);
        if (empty($snapshot)) {
            return;
        }

        FbmCampaignDraftAsset::query()->create([
            'fbm_campaign_draft_id' => (int) $draft->id,
            'asset_type' => $type,
            'asset_id' => $id,
            'role' => $role,
            'snapshot' => $snapshot,
        ]);
    }

    private function assetSnapshot(string $type, int $id): array
    {
        if ($type === 'creative_asset') {
            $asset = FbmCreativeAsset::query()->find($id);
            return $asset ? ['id' => (int) $asset->id, 'title' => (string) $asset->title, 'asset_type' => (string) $asset->asset_type, 'status' => (string) $asset->status] : [];
        }
        if ($type === 'audience') {
            $audience = FbmAudience::query()->find($id);
            return $audience ? ['id' => (int) $audience->id, 'name' => (string) $audience->audience_name, 'type' => (string) $audience->audience_type, 'selected' => (bool) $audience->is_selected] : [];
        }
        if ($type === 'product_set') {
            $set = FbmProductSet::query()->find($id);
            return $set ? ['id' => (int) $set->id, 'name' => (string) $set->set_name, 'item_count' => $set->item_count === null ? null : (int) $set->item_count, 'selected' => (bool) $set->is_selected] : [];
        }

        return [];
    }

    private function publishSnapshotPayload(FbmCampaignDraft $draft): array
    {
        return [
            'draft' => $draft->toSafeSummary(),
            'assets' => $draft->assets->map(fn(FbmCampaignDraftAsset $asset): array => [
                'asset_type' => (string) $asset->asset_type,
                'role' => (string) $asset->role,
                'snapshot' => is_array($asset->snapshot) ? $asset->snapshot : [],
            ])->values()->all(),
            'boundary' => 'local_publish_preparation_only',
        ];
    }

    private function mutableDraft(int $draftId): ?FbmCampaignDraft
    {
        return FbmCampaignDraft::query()->find($draftId);
    }

    private function recordApproval(FbmCampaignDraft $draft, string $action, ?string $from, string $to, ?int $userId, ?string $comment, array $metadata): void
    {
        FbmCampaignDraftApproval::query()->create([
            'fbm_campaign_draft_id' => (int) $draft->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'safe_metadata' => $metadata,
            'actor_user_id' => $userId,
            'comment' => $this->safeString($comment, 500),
            'created_at' => now(),
        ]);
    }

    private function readiness(FbmCampaignDraft $draft): array
    {
        $assets = $draft->assets()->get();

        return [
            'has_creative' => $assets->where('asset_type', 'creative_asset')->isNotEmpty(),
            'has_audience' => $assets->where('asset_type', 'audience')->isNotEmpty(),
            'has_product_set' => $assets->where('asset_type', 'product_set')->isNotEmpty(),
            'has_budget' => (float) $draft->budget_amount > 0,
            'has_schedule' => $draft->starts_at !== null,
        ];
    }

    private function approvals(): Collection
    {
        return FbmCampaignDraftApproval::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(max(1, (int) config('fb_marketing.campaign_drafts.approval_history_limit', 30)))
            ->get()
            ->map(fn(FbmCampaignDraftApproval $approval): array => $approval->toSafeSummary());
    }

    private function connections(): Collection
    {
        return FbmConnection::query()->where('is_active', true)->orderBy('connection_name')->limit(200)->get(['id', 'connection_name'])
            ->map(fn(FbmConnection $connection): array => ['id' => (int) $connection->id, 'label' => (string) $connection->connection_name]);
    }

    private function adAccounts(): Collection
    {
        return FbmAdAccount::query()->where('is_available', true)->where('is_selected', true)->orderBy('asset_name')->limit(200)->get(['id', 'asset_name'])
            ->map(fn(FbmAdAccount $account): array => ['id' => (int) $account->id, 'label' => (string) $account->asset_name]);
    }

    private function pages(): Collection
    {
        return FbmPage::query()->where('is_available', true)->where('is_selected', true)->orderBy('asset_name')->limit(200)->get(['id', 'asset_name'])
            ->map(fn(FbmPage $page): array => ['id' => (int) $page->id, 'label' => (string) $page->asset_name]);
    }

    private function creativeAssets(): Collection
    {
        return FbmCreativeAsset::query()->where('status', 'ready')->orderBy('title')->limit(200)->get(['id', 'title', 'asset_type'])
            ->map(fn(FbmCreativeAsset $asset): array => ['id' => (int) $asset->id, 'label' => $asset->title . ' (' . $asset->asset_type . ')']);
    }

    private function audiences(): Collection
    {
        return FbmAudience::query()->where('is_selected', true)->where('is_available', true)->orderBy('audience_name')->limit(200)->get(['id', 'audience_name', 'audience_type'])
            ->map(fn(FbmAudience $audience): array => ['id' => (int) $audience->id, 'label' => $audience->audience_name . ' (' . $audience->audience_type . ')']);
    }

    private function productSets(): Collection
    {
        return FbmProductSet::query()->where('is_selected', true)->where('is_available', true)->orderBy('set_name')->limit(200)->get(['id', 'set_name'])
            ->map(fn(FbmProductSet $set): array => ['id' => (int) $set->id, 'label' => (string) ($set->set_name ?: 'Product set #' . $set->id)]);
    }

    private function summary(Collection $drafts): array
    {
        return [
            'draft_count' => $drafts->count(),
            'submitted_count' => $drafts->where('status', 'submitted')->count(),
            'approved_count' => $drafts->where('status', 'approved')->count(),
            'rejected_count' => $drafts->where('status', 'rejected')->count(),
        ];
    }

    private function schemaReady(): bool
    {
        foreach (['fbm_campaign_drafts', 'fbm_campaign_draft_assets', 'fbm_campaign_draft_approvals', 'fbm_campaign_publish_snapshots'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function objectiveOptions(): array
    {
        return [
            'OUTCOME_SALES' => 'Sales',
            'OUTCOME_TRAFFIC' => 'Traffic',
            'OUTCOME_ENGAGEMENT' => 'Engagement',
            'OUTCOME_LEADS' => 'Leads',
            'OUTCOME_AWARENESS' => 'Awareness',
            'OUTCOME_APP_PROMOTION' => 'App promotion',
        ];
    }

    private function statusOptions(): array
    {
        return ['draft' => 'Draft', 'submitted' => 'Submitted', 'approved' => 'Approved', 'rejected' => 'Rejected', 'archived' => 'Archived'];
    }

    private function specialCategoryOptions(): array
    {
        return ['NONE' => 'None', 'CREDIT' => 'Credit', 'EMPLOYMENT' => 'Employment', 'HOUSING' => 'Housing', 'ISSUES_ELECTIONS_POLITICS' => 'Issues, elections or politics'];
    }

    private function safeSpecialCategories($values): array
    {
        $allowed = array_keys($this->specialCategoryOptions());

        return collect((array) $values)->map(fn($value) => strtoupper(trim((string) $value)))->filter(fn($value) => in_array($value, $allowed, true))->unique()->values()->all();
    }

    private function existingId(string $table, $value): ?int
    {
        $id = (int) $value;
        if ($id <= 0 || !Schema::hasTable($table)) {
            return null;
        }

        return DB::table($table)->where('id', $id)->exists() ? $id : null;
    }

    private function safeOption($value, array $options, string $fallback): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

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

    private function safeCode($value, int $length): ?string
    {
        $value = $this->safeString($value, $length);

        return $value !== null && preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : null;
    }

    private function safeCurrency($value): ?string
    {
        $value = strtoupper((string) $this->safeString($value, 10));

        return preg_match('/^[A-Z]{3,10}$/', $value) ? $value : null;
    }

    private function safeUrl($value): ?string
    {
        $value = $this->safeString($value, 1000);

        return $value !== null && filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }

    private function dateValue($value): ?string
    {
        if (!is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        return date('Y-m-d H:i:s', strtotime((string) $value));
    }

    private function money($value): float
    {
        return max(0, round((float) $value, 2));
    }

    private function maxRows(): int
    {
        return max(100, min(5000, (int) config('fb_marketing.campaign_drafts.max_rows', 1000)));
    }

    private function warning(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }
}
