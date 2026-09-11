<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmInsightReportRun extends Model
{
    public const MODE_DIRECT = 'direct';
    public const MODE_ASYNC = 'async';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_POLLING = 'polling';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_PARTIAL_SUCCESS = 'partial_success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'fbm_insight_report_runs';
    protected $guarded = [];

    protected $casts = [
        'safe_summary' => 'array',
        'window_start' => 'date',
        'window_end' => 'date',
        'next_poll_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'attempt_count' => 'integer',
        'poll_count' => 'integer',
        'row_count' => 'integer',
        'upserted_count' => 'integer',
        'warning_count' => 'integer',
        'error_count' => 'integer',
    ];

    protected $hidden = [
        'provider_report_run_key',
    ];

    public function connection()
    {
        return $this->belongsTo(FbmConnection::class, 'fbm_connection_id');
    }

    public function adAccount()
    {
        return $this->belongsTo(FbmAdAccount::class, 'fbm_ad_account_id');
    }

    public function syncRun()
    {
        return $this->belongsTo(FbmSyncRun::class, 'fbm_sync_run_id');
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

    public static function activeStatuses(): array
    {
        return [self::STATUS_QUEUED, self::STATUS_RUNNING, self::STATUS_POLLING];
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'report_uuid' => (string) $this->report_uuid,
            'fbm_sync_run_id' => $this->fbm_sync_run_id ? (int) $this->fbm_sync_run_id : null,
            'fbm_connection_id' => (int) $this->fbm_connection_id,
            'connection_name' => optional($this->connection)->connection_name,
            'fbm_ad_account_id' => (int) $this->fbm_ad_account_id,
            'ad_account_name' => optional($this->adAccount)->asset_name,
            'insight_level' => (string) $this->insight_level,
            'window_start' => optional($this->window_start)->toDateString(),
            'window_end' => optional($this->window_end)->toDateString(),
            'execution_mode' => (string) $this->execution_mode,
            'status' => (string) $this->status,
            'attempt_count' => (int) $this->attempt_count,
            'poll_count' => (int) $this->poll_count,
            'row_count' => (int) $this->row_count,
            'upserted_count' => (int) $this->upserted_count,
            'warning_count' => (int) $this->warning_count,
            'error_count' => (int) $this->error_count,
            'next_poll_at' => optional($this->next_poll_at)->toDateTimeString(),
            'started_at' => optional($this->started_at)->toDateTimeString(),
            'completed_at' => optional($this->completed_at)->toDateTimeString(),
            'redacted_message' => $this->redacted_message,
            'safe_summary' => is_array($this->safe_summary) ? $this->safe_summary : [],
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
