<?php

namespace App\Models\ServiceManagement;

use App\Http\Controllers\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class ServiceInstance extends Model
{
    protected $table = 'srms_service_instances';

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'billing_unit_qty' => 'decimal:4',
        'service_unit_price' => 'decimal:2',
        'service_subtotal' => 'decimal:2',
        'products_subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'billed_at' => 'datetime',
        'closed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'rental_returned_at' => 'datetime',
        'inventory_applied_at' => 'datetime',
        'accounting_posted_at' => 'datetime',
    ];

    public const STATUSES = ['draft', 'confirmed', 'billed', 'paid', 'cancelled'];

    public function isRental(): bool
    {
        return $this->service?->type === 'rental';
    }

    public function canConfirm(): bool
    {
        return $this->status === 'draft';
    }

    public function canBill(): bool
    {
        return $this->status === 'confirmed';
    }

    public function canClose(): bool
    {
        return in_array($this->status, ['confirmed', 'billed', 'paid'], true) && !$this->closed_at;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'confirmed'], true)
            && !$this->accounting_posted_at
            && (float) $this->paid_amount <= 0;
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function products()
    {
        return $this->hasMany(ServiceInstanceProduct::class, 'service_instance_id');
    }

    public function payments()
    {
        return $this->hasMany(ServicePayment::class, 'service_instance_id');
    }
}
