<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmConversionEventAttempt extends Model
{
    public const STATUS_DRY_RUN_VALIDATED = 'dry_run_validated';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_RETRYABLE_FAILED = 'retryable_failed';
    public const STATUS_PERMANENT_FAILED = 'permanent_failed';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'fbm_conversion_event_attempts';

    public $timestamps = false;

    protected $fillable = [
        'fbm_conversion_event_id',
        'attempt_number',
        'origin',
        'delivery_mode',
        'status',
        'http_status',
        'provider_error_code',
        'provider_error_subcode',
        'redacted_message',
        'request_fingerprint',
        'duration_ms',
        'attempted_at',
        'created_at',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'http_status' => 'integer',
        'duration_ms' => 'integer',
        'attempted_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'request_fingerprint',
    ];

    public function event()
    {
        return $this->belongsTo(FbmConversionEvent::class, 'fbm_conversion_event_id');
    }

    public function toSafeSummary(): array
    {
        return [
            'event_uuid' => $this->relationLoaded('event') ? optional($this->event)->event_uuid : null,
            'event_name' => $this->relationLoaded('event') ? optional($this->event)->event_name : null,
            'attempt_number' => (int) $this->attempt_number,
            'origin' => (string) $this->origin,
            'delivery_mode' => (string) $this->delivery_mode,
            'status' => (string) $this->status,
            'http_status' => $this->http_status === null ? null : (int) $this->http_status,
            'provider_error_code' => $this->provider_error_code,
            'provider_error_subcode' => $this->provider_error_subcode,
            'redacted_message' => $this->redacted_message,
            'duration_ms' => $this->duration_ms === null ? null : (int) $this->duration_ms,
            'attempted_at' => optional($this->attempted_at)->toDateTimeString(),
        ];
    }
}
