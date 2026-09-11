<?php

namespace App\Models\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class CrmCampaignDispatchRecipientAttempt extends Model
{
    protected $table = 'crm_campaign_dispatch_recipient_attempts';

    public $timestamps = false;

    protected $fillable = [
        'product_website_id',
        'crm_campaign_draft_id',
        'crm_campaign_dispatch_preparation_id',
        'crm_campaign_dispatch_run_id',
        'crm_campaign_dispatch_execution_batch_id',
        'crm_campaign_dispatch_execution_recipient_id',
        'crm_campaign_dispatch_attempt_id',
        'customer_id',
        'channel',
        'provider_key',
        'status',
        'recipient_idempotency_key',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'recipient_idempotency_key',
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

    public function executionRecipient()
    {
        return $this->belongsTo(CrmCampaignDispatchExecutionRecipient::class, 'crm_campaign_dispatch_execution_recipient_id');
    }

    public function attempt()
    {
        return $this->belongsTo(CrmCampaignDispatchAttempt::class, 'crm_campaign_dispatch_attempt_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function events()
    {
        return $this->hasMany(CrmCampaignDispatchRecipientAttemptEvent::class, 'crm_campaign_dispatch_recipient_attempt_id');
    }
}
