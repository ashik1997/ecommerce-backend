<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmCampaignDraft extends Model
{
    protected $table = 'fbm_campaign_drafts';
    protected $guarded = [];

    protected $casts = [
        'special_ad_categories' => 'array',
        'budget_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'approval_version' => 'integer',
    ];

    public function connection()
    {
        return $this->belongsTo(FbmConnection::class, 'fbm_connection_id');
    }

    public function adAccount()
    {
        return $this->belongsTo(FbmAdAccount::class, 'fbm_ad_account_id');
    }

    public function page()
    {
        return $this->belongsTo(FbmPage::class, 'fbm_page_id');
    }

    public function assets()
    {
        return $this->hasMany(FbmCampaignDraftAsset::class, 'fbm_campaign_draft_id');
    }

    public function approvals()
    {
        return $this->hasMany(FbmCampaignDraftApproval::class, 'fbm_campaign_draft_id');
    }

    public function publishSnapshots()
    {
        return $this->hasMany(FbmCampaignPublishSnapshot::class, 'fbm_campaign_draft_id');
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'draft_uuid' => (string) $this->draft_uuid,
            'connection_name' => optional($this->connection)->connection_name,
            'ad_account_name' => optional($this->adAccount)->asset_name,
            'page_name' => optional($this->page)->asset_name,
            'draft_name' => (string) $this->draft_name,
            'objective' => (string) $this->objective,
            'special_ad_categories' => is_array($this->special_ad_categories) ? $this->special_ad_categories : [],
            'budget_type' => (string) $this->budget_type,
            'budget_amount' => (float) $this->budget_amount,
            'currency' => (string) $this->currency,
            'starts_at' => optional($this->starts_at)->toDateTimeString(),
            'ends_at' => optional($this->ends_at)->toDateTimeString(),
            'optimization_goal' => (string) $this->optimization_goal,
            'billing_event' => (string) $this->billing_event,
            'has_destination_url' => $this->destination_url !== null,
            'utm_source' => (string) $this->utm_source,
            'utm_medium' => (string) $this->utm_medium,
            'utm_campaign' => (string) $this->utm_campaign,
            'status' => (string) $this->status,
            'approval_version' => (int) $this->approval_version,
            'submitted_at' => optional($this->submitted_at)->toDateTimeString(),
            'approved_at' => optional($this->approved_at)->toDateTimeString(),
            'rejected_at' => optional($this->rejected_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
