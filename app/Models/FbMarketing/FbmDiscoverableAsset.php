<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

abstract class FbmDiscoverableAsset extends Model
{
    public const ASSET_TYPE = 'assets';

    protected $guarded = [];

    protected $casts = [
        'is_available' => 'boolean',
        'is_selected' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = [
        'provider_asset_id',
    ];

    public function connection()
    {
        return $this->belongsTo(FbmConnection::class, 'fbm_connection_id');
    }

    public function businessAccount()
    {
        return $this->belongsTo(FbmBusinessAccount::class, 'fbm_business_account_id');
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'asset_type' => static::ASSET_TYPE,
            'fbm_connection_id' => (int) $this->fbm_connection_id,
            'connection_name' => optional($this->connection)->connection_name,
            'fbm_business_account_id' => $this->fbm_business_account_id ? (int) $this->fbm_business_account_id : null,
            'business_account_name' => optional($this->businessAccount)->asset_name,
            'asset_name' => $this->asset_name,
            'asset_relationship' => (string) $this->asset_relationship,
            'provider_status' => $this->provider_status,
            'is_available' => (bool) $this->is_available,
            'is_selected' => (bool) $this->is_selected,
            'last_seen_at' => optional($this->last_seen_at)->toDateTimeString(),
            'metadata' => $this->safeMetadata(),
        ];
    }

    protected function safeMetadata(): array
    {
        return [];
    }
}
