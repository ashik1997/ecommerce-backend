<?php

namespace App\Models\Hrat;

use Illuminate\Database\Eloquent\Model;

class ImportFailedRow extends Model
{
    protected $table = 'hrat_import_failed_rows';
    protected $guarded = [];
    protected $casts = ['row_data' => 'array'];
}
