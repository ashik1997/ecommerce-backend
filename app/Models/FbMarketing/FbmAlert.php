<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmAlert extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';

    protected $table = 'fbm_alerts';
    protected $guarded = [];

    protected $casts = [
        'safe_context' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected $hidden = [
        'dedupe_key',
    ];

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'alert_uuid' => (string) $this->alert_uuid,
            'alert_type' => (string) $this->alert_type,
            'severity' => (string) $this->severity,
            'status' => (string) $this->status,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id ? (int) $this->source_id : null,
            'title' => (string) $this->title,
            'safe_context' => is_array($this->safe_context) ? $this->safe_context : [],
            'first_seen_at' => optional($this->first_seen_at)->toDateTimeString(),
            'last_seen_at' => optional($this->last_seen_at)->toDateTimeString(),
            'resolved_at' => optional($this->resolved_at)->toDateTimeString(),
        ];
    }
}
