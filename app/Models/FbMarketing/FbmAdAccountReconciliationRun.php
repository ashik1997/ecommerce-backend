<?php

namespace App\Models\FbMarketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FbmAdAccountReconciliationRun extends Model
{
    protected $table = 'fbm_ad_account_reconciliation_runs';
    protected $guarded = [];

    protected $casts = [
        'safe_summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'run_uuid' => (string) $this->run_uuid,
            'actor_user_id' => $this->actor_user_id ? (int) $this->actor_user_id : null,
            'execution_mode' => (string) $this->execution_mode,
            'status' => (string) $this->status,
            'source' => (string) $this->source,
            'sync_run_count' => (int) $this->sync_run_count,
            'failed_sync_count' => (int) $this->failed_sync_count,
            'stale_health_count' => (int) $this->stale_health_count,
            'alert_count' => (int) $this->alert_count,
            'safe_summary' => is_array($this->safe_summary) ? $this->safe_summary : [],
            'redacted_message' => $this->redacted_message,
            'started_at' => optional($this->started_at)->toDateTimeString(),
            'completed_at' => optional($this->completed_at)->toDateTimeString(),
        ];
    }
}
