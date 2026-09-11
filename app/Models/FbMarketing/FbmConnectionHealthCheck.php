<?php

namespace App\Models\FbMarketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FbmConnectionHealthCheck extends Model
{
    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_WARNING = 'warning';
    public const STATUS_FAILED = 'failed';

    protected $table = 'fbm_connection_health_checks';

    public $timestamps = false;

    protected $fillable = [
        'fbm_connection_id',
        'actor_user_id',
        'status',
        'graph_api_version',
        'token_is_valid',
        'token_type',
        'provider_app_id',
        'issued_at',
        'expires_at',
        'data_access_expires_at',
        'scopes',
        'missing_required_scopes',
        'http_status',
        'provider_error_code',
        'provider_error_subcode',
        'redacted_message',
        'duration_ms',
        'request_fingerprint',
        'request_ip_hash',
        'checked_at',
    ];

    protected $casts = [
        'token_is_valid' => 'boolean',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'data_access_expires_at' => 'datetime',
        'scopes' => 'array',
        'missing_required_scopes' => 'array',
        'http_status' => 'integer',
        'duration_ms' => 'integer',
        'checked_at' => 'datetime',
    ];

    protected $hidden = [
        'request_fingerprint',
        'request_ip_hash',
    ];

    public function connection()
    {
        return $this->belongsTo(FbmConnection::class, 'fbm_connection_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Browser-safe allow-listed projection. Raw provider responses, reusable
     * secrets, request fingerprints and IP hashes are deliberately excluded.
     */
    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'fbm_connection_id' => (int) $this->fbm_connection_id,
            'connection_name' => optional($this->connection)->connection_name,
            'actor_user_id' => $this->actor_user_id ? (int) $this->actor_user_id : null,
            'status' => (string) $this->status,
            'graph_api_version' => $this->graph_api_version,
            'token_is_valid' => $this->token_is_valid,
            'token_type' => $this->token_type,
            'provider_app_id' => $this->provider_app_id,
            'issued_at' => optional($this->issued_at)->toDateTimeString(),
            'expires_at' => optional($this->expires_at)->toDateTimeString(),
            'data_access_expires_at' => optional($this->data_access_expires_at)->toDateTimeString(),
            'scopes' => $this->scopes ?: [],
            'missing_required_scopes' => $this->missing_required_scopes ?: [],
            'http_status' => $this->http_status,
            'provider_error_code' => $this->provider_error_code,
            'provider_error_subcode' => $this->provider_error_subcode,
            'redacted_message' => $this->redacted_message,
            'duration_ms' => $this->duration_ms,
            'checked_at' => optional($this->checked_at)->toDateTimeString(),
        ];
    }
}
