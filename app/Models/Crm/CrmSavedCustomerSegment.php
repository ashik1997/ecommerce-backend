<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmSavedCustomerSegment extends Model
{
    use HasFactory;

    protected $table = 'crm_saved_customer_segments';

    protected $fillable = [
        'product_website_id',
        'name',
        'description',
        'filters_json',
        'visibility',
        'status',
        'created_by',
        'updated_by',
        'archived_by',
        'archived_at',
    ];

    protected $casts = [
        'filters_json' => 'array',
        'archived_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function archiver()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }
}
