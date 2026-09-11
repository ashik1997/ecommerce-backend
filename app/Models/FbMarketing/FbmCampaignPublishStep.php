<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmCampaignPublishStep extends Model
{
    protected $table = 'fbm_campaign_publish_steps';
    protected $guarded = [];

    protected $casts = [
        'safe_request_summary' => 'array',
        'safe_response_summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'step_key' => (string) $this->step_key,
            'step_order' => (int) $this->step_order,
            'status' => (string) $this->status,
            'http_method' => (string) $this->http_method,
            'graph_edge' => (string) $this->graph_edge,
            'http_status' => $this->http_status === null ? null : (int) $this->http_status,
            'provider_response_ref' => (string) $this->provider_response_ref,
            'provider_error_code' => (string) $this->provider_error_code,
            'provider_error_subcode' => (string) $this->provider_error_subcode,
            'redacted_message' => (string) $this->redacted_message,
            'safe_request_summary' => is_array($this->safe_request_summary) ? $this->safe_request_summary : [],
            'safe_response_summary' => is_array($this->safe_response_summary) ? $this->safe_response_summary : [],
            'started_at' => optional($this->started_at)->toDateTimeString(),
            'completed_at' => optional($this->completed_at)->toDateTimeString(),
        ];
    }
}
