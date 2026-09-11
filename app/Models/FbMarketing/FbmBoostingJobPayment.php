<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmBoostingJobPayment extends Model
{
    protected $table = 'fbm_boosting_job_payments';
    protected $guarded = [];

    protected $casts = [
        'fbm_boosting_job_id' => 'integer',
        'payment_date' => 'date',
        'amount' => 'float',
    ];
}
