<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmCreativeAsset extends Model
{
    protected $table = 'fbm_creative_assets';
    protected $guarded = [];

    protected $casts = [
        'media_file_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function variants()
    {
        return $this->hasMany(FbmCreativeAssetVariant::class, 'fbm_creative_asset_id');
    }

    public function preflightChecks()
    {
        return $this->hasMany(FbmCreativePreflightCheck::class, 'fbm_creative_asset_id');
    }
}
