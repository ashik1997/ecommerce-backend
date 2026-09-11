<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmCampaignDraftApproval extends Model
{
    protected $table = 'fbm_campaign_draft_approvals';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'safe_metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'fbm_campaign_draft_id' => (int) $this->fbm_campaign_draft_id,
            'action' => (string) $this->action,
            'from_status' => (string) $this->from_status,
            'to_status' => (string) $this->to_status,
            'safe_metadata' => is_array($this->safe_metadata) ? $this->safe_metadata : [],
            'comment' => (string) $this->comment,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
