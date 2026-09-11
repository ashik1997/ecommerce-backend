<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CrmCampaignDraftApprovalHistory extends Model
{
    protected $table = 'crm_campaign_draft_approval_history';

    public $timestamps = false;

    protected $fillable = [
        'product_website_id',
        'crm_campaign_draft_id',
        'action',
        'from_status',
        'to_status',
        'actor_id',
        'review_note',
        'audience_snapshot_signature',
        'audience_snapshot_signature_version',
        'approved_snapshot_signature',
        'approved_snapshot_signature_version',
        'approved_snapshot_at',
        'approved_recipient_set_signature',
        'approved_recipient_set_signature_version',
        'approved_recipient_count',
        'metadata_json',
        'created_at',
    ];

    protected $casts = [
        'audience_snapshot_signature_version' => 'integer',
        'approved_snapshot_signature_version' => 'integer',
        'approved_snapshot_at' => 'datetime',
        'approved_recipient_set_signature_version' => 'integer',
        'approved_recipient_count' => 'integer',
        'metadata_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function draft()
    {
        return $this->belongsTo(CrmCampaignDraft::class, 'crm_campaign_draft_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
