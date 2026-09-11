<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmCampaignPublishAttempt extends Model
{
    protected $table = 'fbm_campaign_publish_attempts';
    protected $guarded = [];

    protected $casts = [
        'safe_response_summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function draft()
    {
        return $this->belongsTo(FbmCampaignDraft::class, 'fbm_campaign_draft_id');
    }

    public function snapshot()
    {
        return $this->belongsTo(FbmCampaignPublishSnapshot::class, 'fbm_campaign_publish_snapshot_id');
    }

    public function steps()
    {
        return $this->hasMany(FbmCampaignPublishStep::class, 'fbm_campaign_publish_attempt_id');
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'attempt_uuid' => (string) $this->attempt_uuid,
            'fbm_campaign_draft_id' => (int) $this->fbm_campaign_draft_id,
            'status' => (string) $this->status,
            'execution_mode' => (string) $this->execution_mode,
            'safe_response_summary' => is_array($this->safe_response_summary) ? $this->safe_response_summary : [],
            'redacted_message' => (string) $this->redacted_message,
            'started_at' => optional($this->started_at)->toDateTimeString(),
            'completed_at' => optional($this->completed_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
