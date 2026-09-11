<?php

namespace App\Models\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class CrmCampaignDispatchRecipient extends Model
{
    protected $table = 'crm_campaign_dispatch_recipients';

    public $timestamps = false;

    protected $fillable = [
        'product_website_id',
        'crm_campaign_draft_id',
        'crm_campaign_dispatch_preparation_id',
        'customer_id',
        'channel',
        'destination_ciphertext',
        'destination_hash',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'destination_ciphertext',
        'destination_hash',
    ];

    public function preparation()
    {
        return $this->belongsTo(CrmCampaignDispatchPreparation::class, 'crm_campaign_dispatch_preparation_id');
    }

    public function draft()
    {
        return $this->belongsTo(CrmCampaignDraft::class, 'crm_campaign_draft_id');
    }

    public function runRecipients()
    {
        return $this->hasMany(CrmCampaignDispatchRunRecipient::class, 'crm_campaign_dispatch_recipient_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
