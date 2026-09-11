<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CrmCampaignDispatchAttempt extends Model
{
    protected $table = 'crm_campaign_dispatch_attempts';

    protected $fillable = [
        'product_website_id',
        'crm_campaign_draft_id',
        'crm_campaign_dispatch_preparation_id',
        'crm_campaign_dispatch_run_id',
        'crm_campaign_dispatch_execution_batch_id',
        'attempt_number',
        'status',
        'active_slot',
        'channel',
        'provider_key',
        'provider_request_snapshot_version',
        'provider_request_snapshot_json',
        'recipient_count',
        'attempt_idempotency_key',
        'attempt_integrity_signature',
        'attempt_integrity_signature_version',
        'provider_request_count',
        'provider_success_count',
        'provider_failure_count',
        'provider_unknown_count',
        'prepared_by',
        'prepared_at',
        'started_by',
        'started_at',
        'completed_at',
        'failed_at',
        'failure_summary',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'metadata_json',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'active_slot' => 'integer',
        'provider_request_snapshot_version' => 'integer',
        'provider_request_snapshot_json' => 'array',
        'recipient_count' => 'integer',
        'attempt_integrity_signature_version' => 'integer',
        'provider_request_count' => 'integer',
        'provider_success_count' => 'integer',
        'provider_failure_count' => 'integer',
        'provider_unknown_count' => 'integer',
        'prepared_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata_json' => 'array',
    ];

    protected $hidden = [
        'provider_request_snapshot_json',
        'attempt_idempotency_key',
        'attempt_integrity_signature',
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

    public function executionBatch()
    {
        return $this->belongsTo(CrmCampaignDispatchExecutionBatch::class, 'crm_campaign_dispatch_execution_batch_id');
    }

    public function recipients()
    {
        return $this->hasMany(CrmCampaignDispatchRecipientAttempt::class, 'crm_campaign_dispatch_attempt_id');
    }

    public function recipientEvents()
    {
        return $this->hasMany(CrmCampaignDispatchRecipientAttemptEvent::class, 'crm_campaign_dispatch_attempt_id');
    }

    public function preparer()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function starter()
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
