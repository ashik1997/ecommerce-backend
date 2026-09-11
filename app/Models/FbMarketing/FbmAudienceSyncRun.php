<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmAudienceSyncRun extends Model
{
    protected $table = 'fbm_audience_sync_runs';
    protected $guarded = [];

    protected $casts = [
        'saved_audience_count' => 'integer',
        'custom_audience_count' => 'integer',
        'warnings' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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
            'connection_name' => optional($this->connection)->connection_name,
            'ad_account_name' => optional($this->adAccount)->asset_name,
            'execution_mode' => (string) $this->execution_mode,
            'status' => (string) $this->status,
            'graph_api_version' => (string) $this->graph_api_version,
            'saved_audience_count' => (int) $this->saved_audience_count,
            'custom_audience_count' => (int) $this->custom_audience_count,
            'warnings' => is_array($this->warnings) ? $this->warnings : [],
            'redacted_message' => (string) $this->redacted_message,
            'started_at' => optional($this->started_at)->toDateTimeString(),
            'completed_at' => optional($this->completed_at)->toDateTimeString(),
        ];
    }
}
