<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CrmCampaignDispatchExecutionBatch extends Model
{
    protected $table = 'crm_campaign_dispatch_execution_batches';

    protected $fillable = [
        'product_website_id',
        'crm_campaign_draft_id',
        'crm_campaign_dispatch_preparation_id',
        'crm_campaign_dispatch_run_id',
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
        'subject_snapshot',
        'message_body_snapshot',
        'batch_idempotency_key',
        'execution_integrity_signature',
        'execution_integrity_signature_version',
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
        'execution_integrity_signature_version' => 'integer',
        'claimed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata_json' => 'array',
    ];

    protected $hidden = [
        'approved_snapshot_signature',
        'approved_recipient_set_signature',
        'frozen_recipient_set_signature',
        'run_integrity_signature',
        'subject_snapshot',
        'message_body_snapshot',
        'batch_idempotency_key',
        'execution_integrity_signature',
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

    public function run()
    {
        return $this->belongsTo(CrmCampaignDispatchRun::class, 'crm_campaign_dispatch_run_id');
    }

    public function recipients()
    {
        return $this->hasMany(CrmCampaignDispatchExecutionRecipient::class, 'crm_campaign_dispatch_execution_batch_id');
    }

    public function attempts()
    {
        return $this->hasMany(CrmCampaignDispatchAttempt::class, 'crm_campaign_dispatch_execution_batch_id');
    }

    public function claimant()
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
