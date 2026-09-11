<?php

namespace App\Models\FbMarketing;

use App\Casts\EncryptedNullableString;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FbmConversionEvent extends Model
{
    public const EVENT_PURCHASE = 'Purchase';

    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_DRY_RUN_VALIDATED = 'dry_run_validated';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_RETRYABLE_FAILED = 'retryable_failed';
    public const STATUS_PERMANENT_FAILED = 'permanent_failed';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'fbm_conversion_events';

    protected $fillable = [
        'event_uuid',
        'fbm_connection_id',
        'fbm_visitor_attribution_session_id',
        'fbm_order_attribution_id',
        'event_name',
        'event_id_ciphertext',
        'event_id_hash',
        'destination_id_ciphertext',
        'destination_id_hash',
        'action_source',
        'event_time',
        'event_source_url_ciphertext',
        'user_data_ciphertext',
        'custom_data_ciphertext',
        'delivery_mode_snapshot',
        'status',
        'attempt_count',
        'next_attempt_at',
        'last_attempt_at',
        'delivered_at',
        'created_by',
    ];

    protected $casts = [
        'event_id_ciphertext' => EncryptedNullableString::class,
        'destination_id_ciphertext' => EncryptedNullableString::class,
        'event_source_url_ciphertext' => EncryptedNullableString::class,
        'user_data_ciphertext' => EncryptedNullableString::class,
        'custom_data_ciphertext' => EncryptedNullableString::class,
        'event_time' => 'integer',
        'attempt_count' => 'integer',
        'next_attempt_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected $hidden = [
        'event_id_ciphertext',
        'event_id_hash',
        'destination_id_ciphertext',
        'destination_id_hash',
        'event_source_url_ciphertext',
        'user_data_ciphertext',
        'custom_data_ciphertext',
    ];

    public function connection()
    {
        return $this->belongsTo(FbmConnection::class, 'fbm_connection_id');
    }

    public function attributionSession()
    {
        return $this->belongsTo(FbmVisitorAttributionSession::class, 'fbm_visitor_attribution_session_id');
    }

    public function orderAttribution()
    {
        return $this->belongsTo(FbmOrderAttribution::class, 'fbm_order_attribution_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attempts()
    {
        return $this->hasMany(FbmConversionEventAttempt::class, 'fbm_conversion_event_id');
    }

    public function latestAttempt()
    {
        return $this->hasOne(FbmConversionEventAttempt::class, 'fbm_conversion_event_id')
            ->orderByDesc('attempted_at')
            ->orderByDesc('id');
    }

    public function isDelivered(): bool
    {
        return (string) $this->status === self::STATUS_DELIVERED;
    }

    public function toSafeSummary(): array
    {
        $attempt = $this->relationLoaded('latestAttempt') ? $this->latestAttempt : null;

        return [
            'event_uuid' => (string) $this->event_uuid,
            'fbm_connection_id' => (int) $this->fbm_connection_id,
            'connection_name' => $this->relationLoaded('connection') ? optional($this->connection)->connection_name : null,
            'event_name' => (string) $this->event_name,
            'order_attribution_linked' => $this->fbm_order_attribution_id !== null,
            'delivery_mode' => (string) $this->delivery_mode_snapshot,
            'status' => (string) $this->status,
            'attempt_count' => (int) $this->attempt_count,
            'next_attempt_at' => optional($this->next_attempt_at)->toDateTimeString(),
            'last_attempt_at' => optional($this->last_attempt_at)->toDateTimeString(),
            'delivered_at' => optional($this->delivered_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'latest_attempt_status' => $attempt ? (string) $attempt->status : null,
            'latest_redacted_message' => $attempt?->redacted_message,
        ];
    }
}
