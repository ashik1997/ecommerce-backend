<?php

namespace App\Models\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerTag extends Model
{
    use HasFactory;

    protected $table = 'crm_customer_tags';

    protected $fillable = [
        'product_website_id',
        'name',
        'slug',
        'color',
        'status',
        'created_by',
        'updated_by',
    ];

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'crm_customer_tag_pivots', 'crm_customer_tag_id', 'customer_id')
            ->withPivot(['created_by'])
            ->withTimestamps();
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
