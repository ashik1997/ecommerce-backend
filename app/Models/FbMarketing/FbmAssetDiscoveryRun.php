<?php

namespace App\Models\FbMarketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FbmAssetDiscoveryRun extends Model
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_PARTIAL_SUCCESS = 'partial_success';
    public const STATUS_FAILED = 'failed';

    protected $table = 'fbm_asset_discovery_runs';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'asset_counts' => 'array',
        'successful_families' => 'array',
        'failed_families' => 'array',
        'warning_details' => 'array',
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $hidden = ['request_fingerprint', 'request_ip_hash'];

    public function connection() { return $this->belongsTo(FbmConnection::class, 'fbm_connection_id'); }
    public function actor() { return $this->belongsTo(User::class, 'actor_user_id'); }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'fbm_connection_id' => (int) $this->fbm_connection_id,
            'connection_name' => optional($this->connection)->connection_name,
            'actor_user_id' => $this->actor_user_id ? (int) $this->actor_user_id : null,
            'status' => (string) $this->status,
            'graph_api_version' => $this->graph_api_version,
            'asset_counts' => $this->asset_counts ?: [],
            'successful_families' => $this->successful_families ?: [],
            'failed_families' => $this->failed_families ?: [],
            'warning_details' => $this->warning_details ?: [],
            'redacted_message' => $this->redacted_message,
            'duration_ms' => $this->duration_ms,
            'started_at' => optional($this->started_at)->toDateTimeString(),
            'completed_at' => optional($this->completed_at)->toDateTimeString(),
        ];
    }
}
