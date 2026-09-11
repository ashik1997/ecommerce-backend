<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOrder extends Model
{
    use HasFactory;

    /** @var bool Prevents infinite recursion when resolving fraud_info inside the accessor */
    protected static $resolvingFraudInfo = false;

    protected $guarded = [];

    protected $casts = [
        'other_charges' => 'array',
        'payments' => 'array',
        'request_data' => 'array',
        'delivery_info' => 'array',
        'courier_info' => 'array',
        'order_tracks' => 'array',
        'address_json' => 'array',
        'shipping_date' => 'datetime',
        'fraud_info' => 'array',
    ];

    public function scopeDirectCustomerReceivable($query)
    {
        return $query->where(function ($sourceQuery) {
            $sourceQuery->whereNull('order_source')
                ->orWhere('order_source', '!=', 'ecommerce');
        });
    }

    public function order_products()
    {
        return $this->hasMany(ProductOrderProduct::class, 'product_order_id');
    }

    public function products()
    {
        return $this->hasMany(ProductOrderProduct::class, 'product_order_id');
    }

    public function returns()
    {
        return $this->hasMany(ProductOrderReturn::class, 'product_order_id');
    }

    public function refunds()
    {
        return $this->hasMany(ProductOrderRefund::class, 'product_order_id');
    }

    public function deliveryShipments()
    {
        return $this->hasMany(\App\Models\Delivery\DeliveryShipment::class, 'product_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Http\Controllers\Customer\Models\Customer::class, 'customer_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(\App\Http\Controllers\Inventory\Models\ProductWarehouse::class, 'product_warehouse_id');
    }

    public function contactPerson()
    {
        return $this->belongsTo(\App\Http\Controllers\Customer\Models\CustomerContactPerson::class, 'customer_contact_person_id');
    }

    public function quotation()
    {
        return $this->belongsTo(ProductOrderQuotation::class, 'product_order_quotation_id');
    }

    /**
     * Same shape as the fraud-check API / cache payload, with blank metrics (source/win stay CACHE).
     */
    protected function blankFraudInfoPayload(string $phone): array
    {
        $emptyCourier = [
            'status' => false,
            'message' => '',
            'data' => [
                'success' => 0,
                'cancel' => 0,
                'total' => 0,
                'deliveredPercentage' => 0,
                'returnPercentage' => 0,
            ],
        ];

        return [
            'phone' => $phone,
            'status' => '',
            'score' => 0,
            'total_parcel' => 0,
            'success_parcel' => 0,
            'cancel_parcel' => 0,
            'response' => [
                'steadfast' => $emptyCourier,
                'pathao' => $emptyCourier,
                'redx' => $emptyCourier,
                'carrybee' => $emptyCourier,
            ],
            'source' => 'CACHE',
            'win' => 'CACHE',
        ];
    }

    public function getFraudInfoAttribute($value)
    {
        if (static::$resolvingFraudInfo) {
            return $this->castAttribute('fraud_info', $value);
        }

        $decoded = $this->castAttribute('fraud_info', $value);

        // Raw column value (same as original empty($value)); note empty('[]') is false, so stored [] is not refetched.
        $needsFetch = $this->order_source === 'ecommerce'
            && $this->customer_phone
            && empty($value);

        if (! $needsFetch) {
            return $decoded;
        }

        static::$resolvingFraudInfo = true;
        try {
            $data = fraud_checker($this->customer_phone);
            $this->setAttribute('fraud_info', $data);
            if ($this->exists) {
                $this->saveQuietly();
            }
        } finally {
            static::$resolvingFraudInfo = false;
        }

        if ($data === null) {
            $data = $this->blankFraudInfoPayload((string) $this->customer_phone);
        }

        return $data;
    }


    public function salesman()
    {
        return $this->belongsTo(User::class, 'salesman_id');
    }

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class, 'affiliate_id');
    }

    public function commissionEntries()
    {
        return $this->hasMany(SalesCommissionEntry::class, 'product_order_id');
    }

    public function profitCosts()
    {
        return $this->hasMany(OrderProfitCost::class, 'product_order_id');
    }
}
