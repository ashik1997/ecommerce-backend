<?php

namespace App\Models\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class CrmCampaignDispatchExecutionRecipient extends Model
{
    protected $table = 'crm_campaign_dispatch_execution_recipients';

    public $timestamps = false;

    protected $fillable = [
        'product_website_id',
        'crm_campaign_draft_id',
        'crm_campaign_dispatch_preparation_id',
        'crm_campaign_dispatch_run_id',
        'crm_campaign_dispatch_execution_batch_id',
        'crm_campaign_dispatch_run_recipient_id',
        'customer_id',
        'channel',
        'destination_hash',
        'idempotency_key',
        'status',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'destination_hash',
        'idempotency_key',
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

    public function batch()
    {
        return $this->belongsTo(CrmCampaignDispatchExecutionBatch::class, 'crm_campaign_dispatch_execution_batch_id');
    }

    public function runRecipient()
    {
        return $this->belongsTo(CrmCampaignDispatchRunRecipient::class, 'crm_campaign_dispatch_run_recipient_id');
    }

    public function attemptRecipients()
    {
        return $this->hasMany(CrmCampaignDispatchRecipientAttempt::class, 'crm_campaign_dispatch_execution_recipient_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
