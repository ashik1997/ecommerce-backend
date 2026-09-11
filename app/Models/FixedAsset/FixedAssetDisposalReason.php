<?php

namespace App\Models\FixedAsset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetDisposalReason extends Model
{
    use HasFactory;

    protected $table = 'fa_disposal_reasons';
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function disposals()
    {
        return $this->hasMany(FixedAssetDisposal::class, 'disposal_reason_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
