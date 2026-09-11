<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmLeadAdWebhookLog extends Model
{
    protected $table = 'fbm_lead_ad_webhook_logs';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'request_fingerprint',
    ];

    public function connection()
    {
        return $this->belongsTo(FbmConnection::class, 'fbm_connection_id');
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'connection_name' => optional($this->connection)->connection_name,
            'event_type' => (string) $this->event_type,
            'status' => (string) $this->status,
            'redacted_message' => (string) $this->redacted_message,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
