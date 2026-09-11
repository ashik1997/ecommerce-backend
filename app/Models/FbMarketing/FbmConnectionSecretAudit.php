<?php

namespace App\Models\FbMarketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FbmConnectionSecretAudit extends Model
{
    protected $table = 'fbm_connection_secret_audits';

    public $timestamps = false;

    protected $fillable = [
        'fbm_connection_id',
        'action',
        'actor_user_id',
        'changed_fields',
        'configured_secret_fields',
        'secret_fingerprints',
        'before_state',
        'after_state',
        'request_ip_hash',
        'change_reason',
        'created_at',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'configured_secret_fields' => 'array',
        'secret_fingerprints' => 'array',
        'before_state' => 'array',
        'after_state' => 'array',
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'secret_fingerprints',
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
     * Return the append-only audit information that is safe to render.
     */
    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'fbm_connection_id' => (int) $this->fbm_connection_id,
            'connection_name' => optional($this->connection)->connection_name,
            'action' => (string) $this->action,
            'actor_user_id' => $this->actor_user_id ? (int) $this->actor_user_id : null,
            'changed_fields' => $this->changed_fields ?: [],
            'configured_secret_fields' => $this->configured_secret_fields ?: [],
            'change_reason' => $this->change_reason,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
