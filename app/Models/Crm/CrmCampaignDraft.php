<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmCampaignDraft extends Model
{
    use HasFactory;

    protected $table = 'crm_campaign_drafts';

    protected $fillable = [
        'product_website_id',
        'name',
        'description',
        'planned_channel',
        'subject',
        'message_body',
        'audience_saved_segment_id',
        'audience_segment_name_snapshot',
        'audience_segment_visibility_snapshot',
        'audience_filters_json',
        'audience_snapshot_at',
        'audience_snapshot_signature',
        'audience_snapshot_signature_version',
        'audience_snapshot_share_token',
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_decision',
        'review_note',
        'approved_snapshot_signature',
        'approved_snapshot_signature_version',
        'approved_snapshot_at',
        'approved_recipient_set_signature',
        'approved_recipient_set_signature_version',
        'approved_recipient_count',
        'visibility',
        'status',
        'created_by',
        'updated_by',
        'archived_by',
        'archived_at',
    ];

    protected $casts = [
        'audience_filters_json' => 'array',
        'audience_snapshot_at' => 'datetime',
        'audience_snapshot_signature_version' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_snapshot_signature_version' => 'integer',
        'approved_snapshot_at' => 'datetime',
        'approved_recipient_set_signature_version' => 'integer',
        'approved_recipient_count' => 'integer',
        'archived_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function archiver()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvalHistory()
    {
        return $this->hasMany(CrmCampaignDraftApprovalHistory::class, 'crm_campaign_draft_id');
    }

    public function audienceSavedSegment()
    {
        return $this->belongsTo(CrmSavedCustomerSegment::class, 'audience_saved_segment_id');
    }

    public function dispatchPreparations()
    {
        return $this->hasMany(CrmCampaignDispatchPreparation::class, 'crm_campaign_draft_id');
    }

    public function dispatchRuns()
    {
        return $this->hasMany(CrmCampaignDispatchRun::class, 'crm_campaign_draft_id');
    }

    public function dispatchExecutionBatches()
    {
        return $this->hasMany(CrmCampaignDispatchExecutionBatch::class, 'crm_campaign_draft_id');
    }

    public function dispatchAttempts()
    {
        return $this->hasMany(CrmCampaignDispatchAttempt::class, 'crm_campaign_draft_id');
    }
}
