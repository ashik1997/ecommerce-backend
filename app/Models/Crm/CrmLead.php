<?php

namespace App\Models\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmLead extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_leads';

    protected $fillable = [
        'product_website_id',
        'customer_id',
        'assigned_user_id',
        'name',
        'company_name',
        'phone',
        'email',
        'source',
        'status',
        'priority',
        'estimated_value',
        'score',
        'requirement',
        'lost_reason',
        'next_follow_up_at',
        'qualified_at',
        'converted_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'score' => 'integer',
        'next_follow_up_at' => 'datetime',
        'qualified_at' => 'datetime',
        'converted_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
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
