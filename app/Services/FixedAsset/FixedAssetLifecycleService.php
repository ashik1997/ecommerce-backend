<?php

namespace App\Services\FixedAsset;

use App\Models\FixedAsset\FixedAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FixedAssetLifecycleService
{
    public function __construct(
        protected AssetCodeService $codeService,
        protected FixedAssetEventService $eventService,
        protected FixedAssetAccountingPostingService $postingService
    ) {}

    public function create(array $payload): FixedAsset
    {
        return DB::transaction(function () use ($payload) {
            $data = $this->prepareAssetData($payload, true);
            $data['uuid'] = (string) Str::uuid();
            $data['asset_code'] = $data['asset_code'] ?? $this->codeService->generate($data['category_id']);
            $data['created_by'] = auth()->id();

            $asset = FixedAsset::create($data);
            $this->postingService->postCapitalization($asset);
            $this->eventService->record($asset, 'asset_created', null, $asset->toArray(), 'Asset registered, warehouse assigned, and capitalization journal posted.');

            return $asset;
        });
    }

    public function update(FixedAsset $asset, array $payload): FixedAsset
    {
        return DB::transaction(function () use ($asset, $payload) {
            $before = $asset->toArray();
            $asset->update($this->prepareAssetData($payload, false) + ['updated_by' => auth()->id()]);
            $asset = $asset->fresh();
            $this->eventService->record($asset, 'asset_updated', $before, $asset->toArray(), 'Asset information updated.');

            return $asset;
        });
    }

    public function archive(FixedAsset $asset): FixedAsset
    {
        return DB::transaction(function () use ($asset) {
            $before = $asset->toArray();
            $asset->update([
                'lifecycle_status' => 'archived',
                'operational_status' => 'retired',
                'updated_by' => auth()->id(),
            ]);
            $asset = $asset->fresh();
            $this->eventService->record($asset, 'asset_archived', $before, $asset->toArray(), 'Asset archived.');

            return $asset;
        });
    }

    private function prepareAssetData(array $data, bool $isCreate): array
    {
        $data['additional_cost'] = $data['additional_cost'] ?? 0;
        $data['residual_value'] = $data['residual_value'] ?? 0;
        $data['capitalized_cost'] = (float) $data['purchase_cost'] + (float) $data['additional_cost'];

        if ($isCreate) {
            $data['carrying_amount'] = $data['capitalized_cost'];
            $data['depreciation_start_date'] = $data['available_for_use_date'] ?? $data['purchase_date'] ?? now()->toDateString();
            $data['lifecycle_status'] = 'capitalized';
            $data['operational_status'] = $data['operational_status'] ?? 'available';
        }

        return $data;
    }
}
