<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmCampaignDraftAsset extends Model
{
    protected $table = 'fbm_campaign_draft_assets';
    protected $guarded = [];

    protected $casts = [
        'snapshot' => 'array',
    ];
}
