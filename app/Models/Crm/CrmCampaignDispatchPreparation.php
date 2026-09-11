<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CrmCampaignDispatchPreparation extends Model
{
    protected $table = 'crm_campaign_dispatch_preparations';

    protected $fillable = [
        'product_website_id',
        'crm_campaign_draft_id',
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
        'prepared_by',
        'prepared_at',
        'released_dispatch_run_id',
        'released_by',
        'released_at',
        'invalidated_by',
        'invalidated_at',
        'invalidation_reason',
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
        'prepared_at' => 'datetime',
        'released_at' => 'datetime',
        'invalidated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata_json' => 'array',
    ];

    public function draft()
    {
        return $this->belongsTo(CrmCampaignDraft::class, 'crm_campaign_draft_id');
    }

    public function recipients()
    {
        return $this->hasMany(CrmCampaignDispatchRecipient::class, 'crm_campaign_dispatch_preparation_id');
    }

    public function preparer()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function releasedRun()
    {
        return $this->belongsTo(CrmCampaignDispatchRun::class, 'released_dispatch_run_id');
    }

    public function executionBatches()
    {
        return $this->hasMany(CrmCampaignDispatchExecutionBatch::class, 'crm_campaign_dispatch_preparation_id');
    }

    public function releaser()
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function invalidator()
    {
        return $this->belongsTo(User::class, 'invalidated_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
