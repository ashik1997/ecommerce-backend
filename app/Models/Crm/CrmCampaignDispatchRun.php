<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CrmCampaignDispatchRun extends Model
{
    protected $table = 'crm_campaign_dispatch_runs';

    protected $fillable = [
        'product_website_id',
        'crm_campaign_draft_id',
        'crm_campaign_dispatch_preparation_id',
        'status',
        'active_slot',
        'channel',
        'approved_snapshot_signature',
        'approved_snapshot_signature_version',
        'approved_snapshot_at',
        'approved_recipient_set_signature',
        'approved_recipient_set_signature_version',
        'approved_recipient_count',
        'frozen_recipient_set_signature',
        'frozen_recipient_set_signature_version',
        'frozen_recipient_count',
        'run_integrity_signature',
        'run_integrity_signature_version',
        'released_by',
        'released_at',
        'claimed_execution_batch_id',
        'claimed_by',
        'claimed_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'metadata_json',
    ];

    protected $casts = [
        'active_slot' => 'integer',
        'approved_snapshot_signature_version' => 'integer',
        'approved_snapshot_at' => 'datetime',
        'approved_recipient_set_signature_version' => 'integer',
        'approved_recipient_count' => 'integer',
        'frozen_recipient_set_signature_version' => 'integer',
        'frozen_recipient_count' => 'integer',
        'run_integrity_signature_version' => 'integer',
        'released_at' => 'datetime',
        'claimed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata_json' => 'array',
    ];

    protected $hidden = [
        'approved_snapshot_signature',
        'approved_recipient_set_signature',
        'frozen_recipient_set_signature',
        'run_integrity_signature',
        'metadata_json',
    ];

    public function draft()
    {
        return $this->belongsTo(CrmCampaignDraft::class, 'crm_campaign_draft_id');
    }

    public function preparation()
    {
        return $this->belongsTo(CrmCampaignDispatchPreparation::class, 'crm_campaign_dispatch_preparation_id');
    }

    public function recipients()
    {
        return $this->hasMany(CrmCampaignDispatchRunRecipient::class, 'crm_campaign_dispatch_run_id');
    }

    public function releaser()
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function claimedExecutionBatch()
    {
        return $this->belongsTo(CrmCampaignDispatchExecutionBatch::class, 'claimed_execution_batch_id');
    }

    public function claimant()
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function executionBatches()
    {
        return $this->hasMany(CrmCampaignDispatchExecutionBatch::class, 'crm_campaign_dispatch_run_id');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
