<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmAttributionReconciliation extends Model
{
    public $timestamps = false;

    protected $table = 'fbm_attribution_reconciliations';

    protected $fillable = [
        'fbm_order_attribution_id',
        'event_type',
        'previous_state',
        'current_state',
        'amount_delta',
        'safe_reason',
        'origin',
        'created_at',
    ];

    protected $casts = [
        'amount_delta' => 'float',
        'created_at' => 'datetime',
    ];

    public function attribution()
    {
        return $this->belongsTo(FbmOrderAttribution::class, 'fbm_order_attribution_id');
    }
}
