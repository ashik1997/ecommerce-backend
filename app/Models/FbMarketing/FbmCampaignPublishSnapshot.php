<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmCampaignPublishSnapshot extends Model
{
    protected $table = 'fbm_campaign_publish_snapshots';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'snapshot_payload' => 'array',
        'approval_version' => 'integer',
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'snapshot_payload',
        'payload_hash',
    ];
}
