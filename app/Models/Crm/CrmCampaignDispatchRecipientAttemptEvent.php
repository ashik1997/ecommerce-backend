<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class CrmCampaignDispatchRecipientAttemptEvent extends Model
{
    protected $table = 'crm_campaign_dispatch_recipient_attempt_events';

    public $timestamps = false;

    protected $fillable = [
        'product_website_id',
        'crm_campaign_draft_id',
        'crm_campaign_dispatch_attempt_id',
        'crm_campaign_dispatch_recipient_attempt_id',
        'crm_campaign_dispatch_execution_batch_id',
        'event_type',
        'provider_key',
        'request_sequence',
        'terminal_slot',
        'status',
        'provider_message_id',
        'provider_response_code',
        'redacted_response_summary',
        'response_hash',
        'request_started_at',
        'request_completed_at',
        'failed_at',
        'failure_category',
        'failure_summary',
        'metadata_json',
        'created_at',
    ];

    protected $casts = [
        'request_sequence' => 'integer',
        'terminal_slot' => 'integer',
        'request_started_at' => 'datetime',
        'request_completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata_json' => 'array',
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'provider_message_id',
        'provider_response_code',
        'redacted_response_summary',
        'response_hash',
        'failure_summary',
        'metadata_json',
    ];

    public function attempt()
    {
        return $this->belongsTo(CrmCampaignDispatchAttempt::class, 'crm_campaign_dispatch_attempt_id');
    }

    public function recipientAttempt()
    {
        return $this->belongsTo(CrmCampaignDispatchRecipientAttempt::class, 'crm_campaign_dispatch_recipient_attempt_id');
    }

    public function executionBatch()
    {
        return $this->belongsTo(CrmCampaignDispatchExecutionBatch::class, 'crm_campaign_dispatch_execution_batch_id');
    }

    public function draft()
    {
        return $this->belongsTo(CrmCampaignDraft::class, 'crm_campaign_draft_id');
    }
}
