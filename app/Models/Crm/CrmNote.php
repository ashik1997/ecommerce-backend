<?php

namespace App\Models\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_notes';

    protected $fillable = [
        'product_website_id',
        'customer_id',
        'note',
        'is_private',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
