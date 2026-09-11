<?php

namespace App\Services\FixedAsset;

use App\Models\FixedAsset\FixedAsset;
use App\Models\FixedAsset\FixedAssetEvent;

class FixedAssetEventService
{
    public function record(FixedAsset $asset, string $eventType, ?array $before = null, ?array $after = null, ?string $note = null, ?string $sourceType = null, ?int $sourceId = null): void
    {
        FixedAssetEvent::create([
            'asset_id' => $asset->id,
            'event_type' => $eventType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'before_json' => $before ? json_encode($before) : null,
            'after_json' => $after ? json_encode($after) : null,
            'note' => $note,
            'created_by' => auth()->id(),
        ]);
    }
}
