<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmAdAccountWebhookLog extends Model
{
    protected $table = 'fbm_ad_account_webhook_logs';
    protected $guarded = [];

    protected $casts = [
        'safe_change_summary' => 'array',
        'received_at' => 'datetime',
    ];

    protected $hidden = [
        'provider_object_id',
        'request_fingerprint',
    ];

    public function connection()
    {
        return $this->belongsTo(FbmConnection::class, 'fbm_connection_id');
    }

    public function adAccount()
    {
        return $this->belongsTo(FbmAdAccount::class, 'fbm_ad_account_id');
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'event_uuid' => (string) $this->event_uuid,
            'connection_name' => optional($this->connection)->connection_name,
            'ad_account_name' => optional($this->adAccount)->asset_name,
            'event_type' => (string) $this->event_type,
            'object_type' => $this->object_type,
            'change_field' => $this->change_field,
            'status' => (string) $this->status,
            'safe_change_summary' => is_array($this->safe_change_summary) ? $this->safe_change_summary : [],
            'redacted_message' => $this->redacted_message,
            'received_at' => optional($this->received_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
