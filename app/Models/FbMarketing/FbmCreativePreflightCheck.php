<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmCreativePreflightCheck extends Model
{
    public $timestamps = false;

    protected $table = 'fbm_creative_preflight_checks';
    protected $guarded = [];

    protected $casts = [
        'checks' => 'array',
        'issue_count' => 'integer',
        'checked_by' => 'integer',
        'checked_at' => 'datetime',
    ];
}
