<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmCampaignOperationalAction extends Model
{
    protected $table = 'fbm_campaign_operational_actions';
    protected $guarded = [];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'budget_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function draft()
    {
        return $this->belongsTo(FbmCampaignDraft::class, 'fbm_campaign_draft_id');
    }

    public function publishAttempt()
    {
        return $this->belongsTo(FbmCampaignPublishAttempt::class, 'fbm_campaign_publish_attempt_id');
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'action_uuid' => (string) $this->action_uuid,
            'fbm_campaign_draft_id' => (int) $this->fbm_campaign_draft_id,
            'draft_name' => optional($this->draft)->draft_name,
            'action_type' => (string) $this->action_type,
            'target_type' => (string) $this->target_type,
            'status' => (string) $this->status,
            'execution_mode' => (string) $this->execution_mode,
            'before_state' => is_array($this->before_state) ? $this->before_state : [],
            'after_state' => is_array($this->after_state) ? $this->after_state : [],
            'budget_amount' => $this->budget_amount === null ? null : (float) $this->budget_amount,
            'budget_type' => (string) $this->budget_type,
            'starts_at' => optional($this->starts_at)->toDateTimeString(),
            'ends_at' => optional($this->ends_at)->toDateTimeString(),
            'reason' => (string) $this->reason,
            'redacted_message' => (string) $this->redacted_message,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'completed_at' => optional($this->completed_at)->toDateTimeString(),
        ];
    }
}
