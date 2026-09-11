<?php

namespace App\Models\FbMarketing;

use App\Casts\EncryptedNullableString;
use Illuminate\Database\Eloquent\Model;

class FbmVisitorAttributionSession extends Model
{
    protected $table = 'fbm_visitor_attribution_sessions';

    protected $fillable = [
        'session_uuid',
        'first_seen_at',
        'last_seen_at',
        'expires_at',
        'capture_count',
        'first_landing_url',
        'latest_landing_url',
        'first_referrer_url',
        'latest_referrer_url',
        'first_utm_source',
        'first_utm_medium',
        'first_utm_campaign',
        'first_utm_content',
        'first_utm_term',
        'first_utm_id',
        'latest_utm_source',
        'latest_utm_medium',
        'latest_utm_campaign',
        'latest_utm_content',
        'latest_utm_term',
        'latest_utm_id',
        'fbclid_ciphertext',
        'fbc_ciphertext',
        'fbp_ciphertext',
        'request_ip_hash',
        'user_agent_hash',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'expires_at' => 'datetime',
        'capture_count' => 'integer',
        'fbclid_ciphertext' => EncryptedNullableString::class,
        'fbc_ciphertext' => EncryptedNullableString::class,
        'fbp_ciphertext' => EncryptedNullableString::class,
    ];

    protected $hidden = [
        'first_landing_url',
        'latest_landing_url',
        'first_referrer_url',
        'latest_referrer_url',
        'first_utm_source',
        'first_utm_medium',
        'first_utm_campaign',
        'first_utm_content',
        'first_utm_term',
        'first_utm_id',
        'latest_utm_source',
        'latest_utm_medium',
        'latest_utm_campaign',
        'latest_utm_content',
        'latest_utm_term',
        'latest_utm_id',
        'fbclid_ciphertext',
        'fbc_ciphertext',
        'fbp_ciphertext',
        'request_ip_hash',
        'user_agent_hash',
    ];

    /**
     * Return only the opaque browser handoff token and bounded expiry.
     */
    public function toBrowserSafeSummary(): array
    {
        return [
            'attribution_session_uuid' => (string) $this->session_uuid,
            'expires_at' => optional($this->expires_at)->toIso8601String(),
            'capture_state' => 'stored',
        ];
    }
}
