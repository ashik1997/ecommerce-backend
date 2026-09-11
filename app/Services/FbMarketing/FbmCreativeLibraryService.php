<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmCreativeAsset;
use App\Models\FbMarketing\FbmCreativePreflightCheck;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FbmCreativeLibraryService
{
    public function build(array $filters): array
    {
        $schemaReady = $this->schemaReady();
        $safeFilters = $this->safeFilters($filters);
        $warnings = [];

        if (!$schemaReady) {
            $warnings[] = $this->warning('danger', 'FBM-21 creative library tables are required before creative assets are available.');
        }

        $assets = $schemaReady ? $this->assets($safeFilters) : collect();

        return [
            'schema_ready' => $schemaReady,
            'filters' => $safeFilters,
            'asset_type_options' => $this->assetTypeOptions(),
            'status_options' => $this->statusOptions(),
            'summary' => $this->summary($assets),
            'rows' => $assets->values()->all(),
            'warnings' => $warnings,
        ];
    }

    public function storeAsset(array $input, ?int $userId): void
    {
        if (!Schema::hasTable('fbm_creative_assets')) {
            return;
        }

        FbmCreativeAsset::query()->create([
            'asset_uuid' => (string) Str::uuid(),
            'asset_type' => $this->safeOption($input['asset_type'] ?? 'image', $this->assetTypeOptions(), 'image'),
            'title' => $this->safeString($input['title'] ?? 'Creative asset', 180) ?: 'Creative asset',
            'primary_text' => $this->safeString($input['primary_text'] ?? null, 1000),
            'headline' => $this->safeString($input['headline'] ?? null, 255),
            'description' => $this->safeString($input['description'] ?? null, 500),
            'call_to_action' => $this->safeString($input['call_to_action'] ?? null, 80),
            'media_file_id' => $this->existingId('media_files', $input['media_file_id'] ?? null),
            'external_asset_url' => $this->safeUrl($input['external_asset_url'] ?? null),
            'landing_url' => $this->safeUrl($input['landing_url'] ?? null),
            'utm_source' => $this->safeCode($input['utm_source'] ?? null, 80),
            'utm_medium' => $this->safeCode($input['utm_medium'] ?? null, 80),
            'utm_campaign' => $this->safeCode($input['utm_campaign'] ?? null, 160),
            'status' => $this->safeOption($input['status'] ?? 'draft', $this->statusOptions(), 'draft'),
            'created_by' => $userId,
        ]);
    }

    public function runPreflight(int $assetId, ?int $userId): void
    {
        if (!Schema::hasTable('fbm_creative_assets') || !Schema::hasTable('fbm_creative_preflight_checks')) {
            return;
        }

        $asset = FbmCreativeAsset::query()->find($assetId);
        if (!$asset) {
            return;
        }

        $checks = $this->checks($asset);
        $issueCount = collect($checks)->where('status', 'fail')->count();

        FbmCreativePreflightCheck::query()->create([
            'fbm_creative_asset_id' => (int) $asset->id,
            'status' => $issueCount > 0 ? 'failed' : 'passed',
            'issue_count' => $issueCount,
            'checks' => $checks,
            'checked_by' => $userId,
            'checked_at' => now(),
        ]);

        $asset->forceFill(['status' => $issueCount > 0 ? 'draft' : 'ready'])->save();
    }

    private function assets(array $filters): Collection
    {
        $query = FbmCreativeAsset::query()
            ->with(['preflightChecks' => fn($query) => $query->orderByDesc('checked_at')->limit(1)])
            ->orderByDesc('id')
            ->limit($this->maxRows());

        if ($filters['asset_type'] !== null) {
            $query->where('asset_type', $filters['asset_type']);
        }
        if ($filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        return $query->get()->map(fn(FbmCreativeAsset $asset): array => $this->safeAsset($asset));
    }

    private function safeAsset(FbmCreativeAsset $asset): array
    {
        $latest = $asset->preflightChecks->first();

        return [
            'id' => (int) $asset->id,
            'asset_type' => (string) $asset->asset_type,
            'title' => (string) $asset->title,
            'headline' => (string) $asset->headline,
            'primary_text_preview' => Str::limit((string) $asset->primary_text, 120),
            'has_media' => (bool) $asset->media_file_id,
            'has_external_asset_url' => $asset->external_asset_url !== null,
            'has_landing_url' => $asset->landing_url !== null,
            'utm_source' => (string) $asset->utm_source,
            'utm_medium' => (string) $asset->utm_medium,
            'utm_campaign' => (string) $asset->utm_campaign,
            'status' => (string) $asset->status,
            'preflight_status' => optional($latest)->status ?: 'not_run',
            'preflight_issues' => (int) optional($latest)->issue_count,
            'preflight_checked_at' => optional(optional($latest)->checked_at)->toDateTimeString(),
            'created_at' => optional($asset->created_at)->toDateTimeString(),
        ];
    }

    private function checks(FbmCreativeAsset $asset): array
    {
        $checks = [];
        $this->addCheck($checks, 'title_present', trim((string) $asset->title) !== '', 'Title is required.');
        $this->addCheck($checks, 'primary_text_present', trim((string) $asset->primary_text) !== '', 'Primary text is required.');
        $this->addCheck($checks, 'headline_present', trim((string) $asset->headline) !== '', 'Headline is required.');
        $this->addCheck($checks, 'media_or_text_ready', $asset->asset_type === 'text' || $asset->media_file_id || $asset->external_asset_url, 'Image/video assets need a media file ID or external asset URL.');
        $this->addCheck($checks, 'landing_url_valid', !$asset->landing_url || filter_var($asset->landing_url, FILTER_VALIDATE_URL), 'Landing URL must be valid when present.');
        $this->addCheck($checks, 'primary_text_length', strlen((string) $asset->primary_text) <= 500, 'Primary text should stay within 500 characters for preflight readiness.');
        $this->addCheck($checks, 'utm_ready', !$asset->landing_url || ($asset->utm_source && $asset->utm_medium && $asset->utm_campaign), 'Landing URL assets should include UTM source, medium and campaign.');

        return $checks;
    }

    private function addCheck(array &$checks, string $key, bool $passed, string $message): void
    {
        $checks[] = ['key' => $key, 'status' => $passed ? 'pass' : 'fail', 'message' => $message];
    }

    private function summary(Collection $assets): array
    {
        return [
            'asset_count' => $assets->count(),
            'ready_count' => $assets->where('status', 'ready')->count(),
            'draft_count' => $assets->where('status', 'draft')->count(),
            'failed_preflight_count' => $assets->where('preflight_status', 'failed')->count(),
        ];
    }

    private function schemaReady(): bool
    {
        foreach (['fbm_creative_assets', 'fbm_creative_asset_variants', 'fbm_creative_preflight_checks'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function safeFilters(array $filters): array
    {
        return [
            'asset_type' => array_key_exists((string) ($filters['asset_type'] ?? ''), $this->assetTypeOptions()) ? (string) $filters['asset_type'] : null,
            'status' => array_key_exists((string) ($filters['status'] ?? ''), $this->statusOptions()) ? (string) $filters['status'] : null,
        ];
    }

    private function assetTypeOptions(): array
    {
        return ['image' => 'Image', 'video' => 'Video', 'carousel' => 'Carousel', 'text' => 'Text', 'product' => 'Product'];
    }

    private function statusOptions(): array
    {
        return ['draft' => 'Draft', 'ready' => 'Ready', 'archived' => 'Archived'];
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
        $value = is_scalar($value) ? strtolower(trim((string) $value)) : '';

        return array_key_exists($value, $options) ? $value : $fallback;
    }

    private function safeCode($value, int $length): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value !== '' && preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? substr($value, 0, $length) : null;
    }

    private function safeUrl($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value !== '' && filter_var($value, FILTER_VALIDATE_URL) ? substr($value, 0, 1000) : null;
    }

    private function safeString($value, int $length): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : substr($value, 0, $length);
    }

    private function maxRows(): int
    {
        return max(100, min(5000, (int) config('fb_marketing.creative_library.max_rows', 1000)));
    }

    private function warning(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }
}
