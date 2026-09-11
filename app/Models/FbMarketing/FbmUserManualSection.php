<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmUserManualSection extends Model
{
    protected $table = 'fbm_user_manual_sections';
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function toSafeSummary(): array
    {
        return [
            'id' => (int) $this->id,
            'locale' => (string) $this->locale,
            'section_key' => (string) $this->section_key,
            'title' => (string) $this->title,
            'body' => (string) $this->body,
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
