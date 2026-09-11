<?php

namespace App\Models\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmActivity extends Model
{
    use HasFactory;

    protected $table = 'crm_activities';

    protected $fillable = [
        'product_website_id',
        'customer_id',
        'activity_type',
        'subject',
        'description',
        'source_module',
        'source_id',
        'metadata',
        'performed_by',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
