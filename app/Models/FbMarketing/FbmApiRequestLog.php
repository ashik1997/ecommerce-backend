<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmApiRequestLog extends Model
{
    protected $table = 'fbm_api_request_logs';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'page_number' => 'integer',
        'http_status' => 'integer',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    protected $hidden = ['request_fingerprint'];

    public function syncRun()
    {
        return $this->belongsTo(FbmSyncRun::class, 'fbm_sync_run_id');
    }

    public function connection()
    {
        return $this->belongsTo(FbmConnection::class, 'fbm_connection_id');
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'fbm_sync_run_id' => $this->fbm_sync_run_id ? (int) $this->fbm_sync_run_id : null,
            'fbm_connection_id' => (int) $this->fbm_connection_id,
            'operation_key' => (string) $this->operation_key,
            'graph_api_version' => $this->graph_api_version,
            'http_method' => (string) $this->http_method,
            'page_number' => $this->page_number === null ? null : (int) $this->page_number,
            'http_status' => $this->http_status === null ? null : (int) $this->http_status,
            'duration_ms' => $this->duration_ms === null ? null : (int) $this->duration_ms,
            'provider_error_code' => $this->provider_error_code,
            'provider_error_subcode' => $this->provider_error_subcode,
            'redacted_message' => $this->redacted_message,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
