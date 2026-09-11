<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmBoostingJob extends Model
{
    protected $table = 'fbm_boosting_jobs';
    protected $guarded = [];

    protected $casts = [
        'customer_id' => 'integer',
        'planned_budget' => 'float',
        'service_fee' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function campaigns()
    {
        return $this->hasMany(FbmBoostingJobCampaign::class, 'fbm_boosting_job_id');
    }

    public function payments()
    {
        return $this->hasMany(FbmBoostingJobPayment::class, 'fbm_boosting_job_id');
    }

    public function costs()
    {
        return $this->hasMany(FbmBoostingJobCostAdjustment::class, 'fbm_boosting_job_id');
    }
}
