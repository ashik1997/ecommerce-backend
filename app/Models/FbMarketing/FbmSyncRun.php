<?php

namespace App\Models\FbMarketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FbmSyncRun extends Model
{
    public const SCOPE_ASSET_DISCOVERY = 'asset_discovery';
    public const SCOPE_FULL_READ_ONLY = 'full_read_only';
    public const SCOPE_MANUAL_DIRECT = 'manual_direct_read_only';
    public const SCOPE_MANUAL_DRILLDOWN = 'manual_drilldown_read_only';

    public const TRIGGER_MANUAL = 'manual';
    public const TRIGGER_MANUAL_DIRECT = 'manual_direct';
    public const TRIGGER_MANUAL_DRILLDOWN = 'manual_drilldown';
    public const TRIGGER_SCHEDULED = 'scheduled';
    public const TRIGGER_CLI = 'cli';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_PARTIAL_SUCCESS = 'partial_success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'fbm_sync_runs';
    protected $guarded = [];

    protected $casts = [
        'safe_summary' => 'array',
        'attempt_count' => 'integer',
        'duration_ms' => 'integer',
        'warning_count' => 'integer',
        'error_count' => 'integer',
        'requested_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $hidden = [
        'application_context_fingerprint',
        'lock_fingerprint',
    ];

    public function connection()
    {
        return $this->belongsTo(FbmConnection::class, 'fbm_connection_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function apiRequestLogs()
    {
        return $this->hasMany(FbmApiRequestLog::class, 'fbm_sync_run_id');
    }

    public static function activeStatuses(): array
    {
        return [self::STATUS_QUEUED, self::STATUS_RUNNING];
    }

    public static function terminalStatuses(): array
    {
        return [
            self::STATUS_SUCCESS,
            self::STATUS_PARTIAL_SUCCESS,
            self::STATUS_FAILED,
            self::STATUS_SKIPPED,
        ];
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'run_uuid' => (string) $this->run_uuid,
            'fbm_connection_id' => (int) $this->fbm_connection_id,
            'connection_name' => optional($this->connection)->connection_name,
            'sync_scope' => (string) $this->sync_scope,
            'trigger_type' => (string) $this->trigger_type,
            'status' => (string) $this->status,
            'requested_by' => $this->requested_by ? (int) $this->requested_by : null,
            'requested_at' => optional($this->requested_at)->toDateTimeString(),
            'started_at' => optional($this->started_at)->toDateTimeString(),
            'completed_at' => optional($this->completed_at)->toDateTimeString(),
            'attempt_count' => (int) $this->attempt_count,
            'duration_ms' => $this->duration_ms === null ? null : (int) $this->duration_ms,
            'warning_count' => (int) $this->warning_count,
            'error_count' => (int) $this->error_count,
            'redacted_message' => $this->redacted_message,
            'safe_summary' => is_array($this->safe_summary) ? $this->safe_summary : [],
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
