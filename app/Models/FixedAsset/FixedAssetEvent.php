<?php

namespace App\Models\FixedAsset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetEvent extends Model
{
    use HasFactory;

    protected $table = 'fa_asset_events';
    protected $guarded = [];

    protected $casts = [
        'before_json' => 'array',
        'after_json' => 'array',
    ];

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }

    public function scopeType($query, $type)
    {
        return $type ? $query->where('event_type', $type) : $query;
    }
}
