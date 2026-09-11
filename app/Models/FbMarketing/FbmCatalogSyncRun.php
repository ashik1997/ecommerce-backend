<?php

namespace App\Models\FbMarketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FbmCatalogSyncRun extends Model
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_PARTIAL_SUCCESS = 'partial_success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    public const MODE_QUEUED_WORKER = 'queued_worker';
    public const MODE_MANUAL_CATALOG_REQUEST = 'manual_catalog_request';
    public const MODE_MANUAL_DIRECT_REQUEST = 'manual_direct_request';

    protected $table = 'fbm_catalog_sync_runs';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'warning_details' => 'array',
        'catalog_count' => 'integer',
        'product_item_count' => 'integer',
        'product_set_count' => 'integer',
        'automatic_mapping_count' => 'integer',
        'manual_mapping_count' => 'integer',
        'unmatched_count' => 'integer',
        'ambiguous_count' => 'integer',
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function connection()
    {
        return $this->belongsTo(FbmConnection::class, 'fbm_connection_id');
    }

    public function catalog()
    {
        return $this->belongsTo(FbmCatalog::class, 'fbm_catalog_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'fbm_connection_id' => (int) $this->fbm_connection_id,
            'connection_name' => optional($this->connection)->connection_name,
            'fbm_catalog_id' => $this->fbm_catalog_id ? (int) $this->fbm_catalog_id : null,
            'catalog_name' => optional($this->catalog)->asset_name,
            'execution_mode' => (string) $this->execution_mode,
            'status' => (string) $this->status,
            'graph_api_version' => $this->graph_api_version,
            'catalog_count' => (int) $this->catalog_count,
            'product_item_count' => (int) $this->product_item_count,
            'product_set_count' => (int) $this->product_set_count,
            'automatic_mapping_count' => (int) $this->automatic_mapping_count,
            'manual_mapping_count' => (int) $this->manual_mapping_count,
            'unmatched_count' => (int) $this->unmatched_count,
            'ambiguous_count' => (int) $this->ambiguous_count,
            'warning_details' => is_array($this->warning_details) ? $this->warning_details : [],
            'redacted_message' => $this->redacted_message,
            'duration_ms' => $this->duration_ms === null ? null : (int) $this->duration_ms,
            'started_at' => optional($this->started_at)->toDateTimeString(),
            'completed_at' => optional($this->completed_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
