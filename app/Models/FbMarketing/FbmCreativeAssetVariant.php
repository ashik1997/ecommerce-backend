<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmCreativeAssetVariant extends Model
{
    protected $table = 'fbm_creative_asset_variants';
    protected $guarded = [];

    protected $casts = [
        'fbm_creative_asset_id' => 'integer',
        'media_file_id' => 'integer',
    ];
}
