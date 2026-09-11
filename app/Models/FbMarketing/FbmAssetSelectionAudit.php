<?php

namespace App\Models\FbMarketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FbmAssetSelectionAudit extends Model
{
    protected $table = 'fbm_asset_selection_audits';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['before_selected' => 'boolean', 'after_selected' => 'boolean', 'created_at' => 'datetime'];
    protected $hidden = ['provider_asset_hash', 'request_ip_hash'];

    public function connection() { return $this->belongsTo(FbmConnection::class, 'fbm_connection_id'); }
    public function actor() { return $this->belongsTo(User::class, 'actor_user_id'); }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'fbm_connection_id' => (int) $this->fbm_connection_id,
            'connection_name' => optional($this->connection)->connection_name,
            'actor_user_id' => $this->actor_user_id ? (int) $this->actor_user_id : null,
            'asset_type' => (string) $this->asset_type,
            'asset_record_id' => (int) $this->asset_record_id,
            'before_selected' => (bool) $this->before_selected,
            'after_selected' => (bool) $this->after_selected,
            'change_reason' => $this->change_reason,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
