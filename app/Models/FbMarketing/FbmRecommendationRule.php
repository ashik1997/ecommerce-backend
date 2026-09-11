<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmRecommendationRule extends Model
{
    protected $table = 'fbm_recommendation_rules';
    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'requires_approval' => 'boolean',
        'safe_conditions' => 'array',
    ];

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'rule_uuid' => (string) $this->rule_uuid,
            'rule_key' => (string) $this->rule_key,
            'rule_type' => (string) $this->rule_type,
            'title' => (string) $this->title,
            'severity' => (string) $this->severity,
            'status' => (string) $this->status,
            'is_enabled' => (bool) $this->is_enabled,
            'requires_approval' => (bool) $this->requires_approval,
            'recommended_action_type' => $this->recommended_action_type,
            'recommended_target_type' => $this->recommended_target_type,
            'safe_conditions' => is_array($this->safe_conditions) ? $this->safe_conditions : [],
            'redacted_message' => $this->redacted_message,
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
