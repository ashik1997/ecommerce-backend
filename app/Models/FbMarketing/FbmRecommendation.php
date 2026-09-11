<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmRecommendation extends Model
{
    public const STATUS_SUGGESTED = 'suggested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DISMISSED = 'dismissed';

    protected $table = 'fbm_recommendations';
    protected $guarded = [];

    protected $casts = [
        'safe_context' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'approved_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    protected $hidden = [
        'dedupe_key',
    ];

    public function rule()
    {
        return $this->belongsTo(FbmRecommendationRule::class, 'fbm_recommendation_rule_id');
    }

    public function alert()
    {
        return $this->belongsTo(FbmAlert::class, 'fbm_alert_id');
    }

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'recommendation_uuid' => (string) $this->recommendation_uuid,
            'rule_title' => optional($this->rule)->title,
            'recommendation_type' => (string) $this->recommendation_type,
            'severity' => (string) $this->severity,
            'status' => (string) $this->status,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id ? (int) $this->source_id : null,
            'title' => (string) $this->title,
            'recommended_action_type' => $this->recommended_action_type,
            'recommended_target_type' => $this->recommended_target_type,
            'safe_context' => is_array($this->safe_context) ? $this->safe_context : [],
            'first_seen_at' => optional($this->first_seen_at)->toDateTimeString(),
            'last_seen_at' => optional($this->last_seen_at)->toDateTimeString(),
            'approved_at' => optional($this->approved_at)->toDateTimeString(),
            'dismissed_at' => optional($this->dismissed_at)->toDateTimeString(),
            'decision_note' => $this->decision_note,
        ];
    }
}
