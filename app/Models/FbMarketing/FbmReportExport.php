<?php

namespace App\Models\FbMarketing;

use Illuminate\Database\Eloquent\Model;

class FbmReportExport extends Model
{
    protected $table = 'fbm_report_exports';
    protected $guarded = [];

    protected $casts = [
        'filter_snapshot' => 'array',
        'row_count' => 'integer',
        'generated_at' => 'datetime',
        'downloaded_at' => 'datetime',
        'requested_by' => 'integer',
    ];

    protected $hidden = [
        'download_token_hash',
    ];
}
