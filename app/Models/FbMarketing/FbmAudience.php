<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmAudience extends Model
{
    protected $table = 'fbm_audiences';
    protected $guarded = [];

    protected $casts = [
        'source_summary' => 'array',
        'approximate_count' => 'integer',
        'is_available' => 'boolean',
        'is_selected' => 'boolean',
        'retention_days' => 'integer',
        'last_synced_at' => 'datetime',
        'selected_at' => 'datetime',
    ];

    protected $hidden = [
        'provider_audience_id',
    ];

    public function connection()
    {
        return $this->belongsTo(FbmConnection::class, 'fbm_connection_id');
    }

    public function adAccount()
    {
        return $this->belongsTo(FbmAdAccount::class, 'fbm_ad_account_id');
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'audience_uuid' => (string) $this->audience_uuid,
            'connection_name' => optional($this->connection)->connection_name,
            'ad_account_name' => optional($this->adAccount)->asset_name,
            'audience_type' => (string) $this->audience_type,
            'audience_name' => (string) $this->audience_name,
            'subtype' => (string) $this->subtype,
            'description' => (string) $this->description,
            'source_summary' => is_array($this->source_summary) ? $this->source_summary : [],
            'approximate_count' => $this->approximate_count === null ? null : (int) $this->approximate_count,
            'status' => (string) $this->status,
            'is_available' => (bool) $this->is_available,
            'is_selected' => (bool) $this->is_selected,
            'planned_use' => (string) $this->planned_use,
            'consent_basis' => (string) $this->consent_basis,
            'consent_note' => (string) $this->consent_note,
            'retention_days' => $this->retention_days === null ? null : (int) $this->retention_days,
            'last_synced_at' => optional($this->last_synced_at)->toDateTimeString(),
            'selected_at' => optional($this->selected_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
