<?php

namespace App\Models\FbMarketing;

use App\Models\Crm\CrmLead;
use Illuminate\Database\Eloquent\Model;

class FbmLeadAdEvent extends Model
{
    protected $table = 'fbm_lead_ad_events';
    protected $guarded = [];

    protected $casts = [
        'field_keys' => 'array',
        'mapped_field_flags' => 'array',
        'received_at' => 'datetime',
        'mapped_at' => 'datetime',
    ];

    protected $hidden = [
        'provider_leadgen_id',
        'provider_form_id',
        'provider_page_id',
        'provider_ad_id',
        'provider_adgroup_id',
        'request_fingerprint',
    ];

    public function connection()
    {
        return $this->belongsTo(FbmConnection::class, 'fbm_connection_id');
    }

    public function crmLead()
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'event_uuid' => (string) $this->event_uuid,
            'connection_name' => optional($this->connection)->connection_name,
            'status' => (string) $this->status,
            'field_keys' => is_array($this->field_keys) ? $this->field_keys : [],
            'mapped_field_flags' => is_array($this->mapped_field_flags) ? $this->mapped_field_flags : [],
            'crm_lead_id' => $this->crm_lead_id ? (int) $this->crm_lead_id : null,
            'redacted_message' => (string) $this->redacted_message,
            'received_at' => optional($this->received_at)->toDateTimeString(),
            'mapped_at' => optional($this->mapped_at)->toDateTimeString(),
        ];
    }
}
