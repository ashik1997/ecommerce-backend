<?php

namespace App\Models\FixedAsset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetVerificationItem extends Model
{
    use HasFactory;

    protected $table = 'fa_verification_items';
    protected $guarded = [];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(FixedAssetVerificationSession::class, 'verification_session_id');
    }

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }

    public function scopeResult($query, $result)
    {
        return $result ? $query->where('result', $result) : $query;
    }
}
