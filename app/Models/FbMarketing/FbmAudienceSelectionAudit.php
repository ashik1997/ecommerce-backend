<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmAudienceSelectionAudit extends Model
{
    protected $table = 'fbm_audience_selection_audits';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'created_at' => 'datetime',
    ];

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'object_type' => (string) $this->object_type,
            'object_id' => (int) $this->object_id,
            'action' => (string) $this->action,
            'before_state' => is_array($this->before_state) ? $this->before_state : [],
            'after_state' => is_array($this->after_state) ? $this->after_state : [],
            'reason' => (string) $this->reason,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
