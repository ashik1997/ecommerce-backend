<?php

namespace App\Services\FbMarketing;

use App\Models\FbMarketing\FbmAssetSelectionAudit;
use App\Models\FbMarketing\FbmDiscoverableAsset;
use App\Models\User;
use App\Support\Security\SecretRedactor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FbmAssetSelectionService
{
    public function updateSelection(
        string $assetType,
        int $assetId,
        bool $isSelected,
        User $actor,
        ?string $ipAddress = null,
        ?string $changeReason = null
    ): FbmDiscoverableAsset {
        $modelClass = $this->modelClass($assetType);

        return DB::transaction(function () use ($modelClass, $assetType, $assetId, $isSelected, $actor, $ipAddress, $changeReason) {
            /** @var FbmDiscoverableAsset|null $asset */
            $asset = $modelClass::query()->lockForUpdate()->find($assetId);
            if (!$asset) {
                throw (new ModelNotFoundException())->setModel($modelClass, [$assetId]);
            }

            if ($isSelected && !$asset->is_available) {
                throw ValidationException::withMessages([
                    'is_selected' => 'Unavailable Meta assets cannot be selected. Run discovery again or choose an available asset.',
                ]);
            }

            $before = (bool) $asset->is_selected;
            if ($before === $isSelected) {
                return $asset;
            }

            $asset->is_selected = $isSelected;
            $asset->save();

            FbmAssetSelectionAudit::create([
                'fbm_connection_id' => (int) $asset->fbm_connection_id,
                'actor_user_id' => (int) $actor->id,
                'asset_type' => $assetType,
                'asset_record_id' => (int) $asset->id,
                'provider_asset_hash' => hash_hmac('sha256', $assetType . '|' . (string) $asset->provider_asset_id, (string) config('app.key', '')),
                'before_selected' => $before,
                'after_selected' => $isSelected,
                'change_reason' => $this->safeReason($changeReason),
                'request_ip_hash' => $this->hashOptionalValue($ipAddress),
                'created_at' => now(),
            ]);

            return $asset->fresh();
        });
    }

    public function modelClass(string $assetType): string
    {
        $assetType = trim($assetType);
        $modelClass = FbmAssetDiscoveryService::ASSET_MODEL_MAP[$assetType] ?? null;

        if (!is_string($modelClass)) {
            throw ValidationException::withMessages(['asset_type' => 'Unsupported FB MARKETING asset type.']);
        }

        return $modelClass;
    }

    protected function safeReason(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = SecretRedactor::redactString($value);

        return strlen($value) <= 500 ? $value : substr($value, 0, 500);
    }

    protected function hashOptionalValue(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : hash_hmac('sha256', $value, (string) config('app.key', ''));
    }
}
