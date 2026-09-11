<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmBoostingJobCostAdjustment extends Model
{
    protected $table = 'fbm_boosting_job_cost_adjustments';
    protected $guarded = [];

    protected $casts = [
        'fbm_boosting_job_id' => 'integer',
        'cost_date' => 'date',
        'base_amount' => 'float',
        'vat_amount' => 'float',
        'tax_amount' => 'float',
        'service_charge_amount' => 'float',
        'total_amount' => 'float',
    ];
}
