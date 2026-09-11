<?php

namespace App\Models\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmCommunication extends Model
{
    use HasFactory;

    protected $table = 'crm_communications';

    protected $fillable = [
        'product_website_id',
        'customer_id',
        'channel',
        'direction',
        'subject',
        'message',
        'status',
        'provider',
        'provider_reference',
        'source_module',
        'sent_by',
        'sent_at',
        'failed_at',
        'failure_reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
