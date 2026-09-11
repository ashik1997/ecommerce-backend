<?php

namespace App\Models\Delivery;

use App\Models\ProductOrder;
use Illuminate\Database\Eloquent\Model;

class DeliveryShipment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'meta' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(ProductOrder::class, 'product_order_id');
    }

    public function provider()
    {
        return $this->belongsTo(DeliveryProvider::class, 'provider_id');
    }

    public function employee()
    {
        return $this->belongsTo(DeliveryEmployee::class, 'delivery_employee_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(DeliveryServiceType::class, 'service_type_id');
    }

    public function zone()
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }

    public function assignments()
    {
        return $this->hasMany(DeliveryAssignment::class, 'delivery_shipment_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(DeliveryShipmentStatusLog::class, 'delivery_shipment_id');
    }

    public function codCollections()
    {
        return $this->hasMany(DeliveryCodCollection::class, 'delivery_shipment_id');
    }

    public function settlementItems()
    {
        return $this->hasMany(DeliverySettlementItem::class, 'delivery_shipment_id');
    }
}
