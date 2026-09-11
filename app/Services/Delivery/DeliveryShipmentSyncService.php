<?php

namespace App\Services\Delivery;

use App\Models\Delivery\DeliveryProvider;
use App\Models\Delivery\DeliveryShipment;
use App\Models\Delivery\DeliveryShipmentStatusLog;
use App\Models\ProductOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DeliveryShipmentSyncService
{
    private const TERMINAL_STATUSES = [
        'delivered',
        'returned',
        'returned_to_store',
        'cancelled',
        'closed',
    ];

    public function syncFromProductOrder(ProductOrder $order, array $deliveryInfo = [], array $context = []): ?DeliveryShipment
    {
        if (!$this->tablesReady() || !$this->hasDeliveryIntent($order, $deliveryInfo, $context)) {
            return null;
        }

        return DB::transaction(function () use ($order, $deliveryInfo, $context) {
            $shipment = DeliveryShipment::query()
                ->where('product_order_id', $order->id)
                ->where('source_type', $context['source_type'] ?? $order->order_source ?? 'pos')
                ->whereNotIn('current_status', self::TERMINAL_STATUSES)
                ->latest('id')
                ->first();

            $previousStatus = $shipment->current_status ?? null;
            $payload = $this->shipmentPayload($order, $deliveryInfo, $context, $shipment);

            if ($shipment) {
                $shipment->update($payload);
            } else {
                $payload['shipment_code'] = $this->makeShipmentCode($order);
                $shipment = DeliveryShipment::create($payload);
            }

            if ($previousStatus !== $shipment->current_status) {
                DeliveryShipmentStatusLog::create([
                    'delivery_shipment_id' => $shipment->id,
                    'status' => $shipment->current_status,
                    'raw_status' => null,
                    'source' => $context['status_source'] ?? 'system',
                    'response_payload' => [
                        'delivery_info' => $deliveryInfo,
                        'context' => $context,
                    ],
                    'note' => $previousStatus
                        ? 'Shipment status refreshed from order delivery data.'
                        : 'Shipment created from order delivery data.',
                    'changed_by' => auth()->id(),
                ]);
            }

            return $shipment->fresh(['provider', 'employee']);
        });
    }

    private function shipmentPayload(ProductOrder $order, array $deliveryInfo, array $context, ?DeliveryShipment $shipment): array
    {
        $provider = $this->resolveProvider($deliveryInfo);
        $currentStatus = $shipment->current_status ?? $this->initialStatus($deliveryInfo);
        $sourceType = $context['source_type'] ?? $order->order_source ?? 'pos';
        $deliveryCharge = $this->deliveryCharge($order, $deliveryInfo, $context);

        return [
            'product_website_id' => $order->product_website_id,
            'product_order_id' => $order->id,
            'provider_id' => $provider ? $provider->id : null,
            'delivery_employee_id' => $deliveryInfo['delivery_employee_id'] ?? null,
            'service_type_id' => $deliveryInfo['service_type_id'] ?? null,
            'zone_id' => $deliveryInfo['zone_id'] ?? null,
            'source_type' => $sourceType,
            'source_reference' => $order->order_code,
            'recipient_name' => $order->customer_name,
            'recipient_phone' => $order->customer_phone,
            'recipient_address' => $deliveryInfo['courier_address'] ?? $order->address,
            'district_id' => $deliveryInfo['district_id'] ?? null,
            'upazila_id' => $deliveryInfo['upazila_id'] ?? null,
            'area_id' => $deliveryInfo['area_id'] ?? null,
            'cod_amount' => $this->codAmount($order),
            'delivery_charge' => $deliveryCharge,
            'provider_cost' => $deliveryInfo['provider_cost'] ?? 0,
            'customer_delivery_charge' => $deliveryCharge,
            'weight' => $deliveryInfo['weight'] ?? null,
            'item_quantity' => $this->itemQuantity($order),
            'tracking_number' => $deliveryInfo['tracking_number'] ?? $shipment->tracking_number ?? null,
            'external_reference' => $deliveryInfo['external_reference'] ?? $shipment->external_reference ?? null,
            'current_status' => $currentStatus,
            'payment_status' => $this->paymentStatus($order),
            'settlement_status' => $shipment->settlement_status ?? 'pending',
            'assigned_at' => $shipment->assigned_at ?? null,
            'meta' => [
                'delivery_method' => $deliveryInfo['delivery_method'] ?? null,
                'local_delivery_provider_id' => $deliveryInfo['local_delivery_provider_id'] ?? $deliveryInfo['delivery_provider_id'] ?? null,
                'local_delivery_provider_name' => $deliveryInfo['local_delivery_provider_name'] ?? null,
                'legacy_courier_method' => $deliveryInfo['courier_method'] ?? null,
                'legacy_courier_method_title' => $deliveryInfo['courier_method_title'] ?? null,
                'expected_delivery_date' => $deliveryInfo['expected_delivery_date'] ?? null,
                'order_note' => $deliveryInfo['order_note'] ?? null,
                'outlet_id' => $deliveryInfo['outlet_id'] ?? null,
            ],
            'creator' => $shipment->creator ?? $order->creator ?? auth()->id(),
        ];
    }

    private function resolveProvider(array $deliveryInfo): ?DeliveryProvider
    {
        if (!Schema::hasTable('delivery_providers')) {
            return null;
        }

        foreach (['delivery_provider_id', 'provider_id'] as $key) {
            if (!empty($deliveryInfo[$key])) {
                $provider = DeliveryProvider::find($deliveryInfo[$key]);
                if ($provider) {
                    return $provider;
                }
            }
        }

        $legacyTitle = trim((string) ($deliveryInfo['courier_method_title'] ?? ''));

        if ($legacyTitle === '' && !empty($deliveryInfo['courier_method']) && Schema::hasTable('product_order_courier_methods')) {
            $legacyTitle = (string) DB::table('product_order_courier_methods')
                ->where('id', $deliveryInfo['courier_method'])
                ->value('title');
        }

        if ($legacyTitle !== '') {
            $slug = Str::slug($legacyTitle);

            return DeliveryProvider::query()
                ->where('slug', $slug)
                ->orWhereRaw('LOWER(name) = ?', [Str::lower($legacyTitle)])
                ->first();
        }

        $deliveryMethod = Str::slug((string) ($deliveryInfo['delivery_method'] ?? ''));
        if (in_array($deliveryMethod, ['store-pickup', 'store_pickup'], true)) {
            return DeliveryProvider::query()
                ->whereIn('provider_type', ['store_pickup', 'internal_fleet'])
                ->orderByRaw("provider_type = 'store_pickup' desc")
                ->first();
        }

        return null;
    }

    private function hasDeliveryIntent(ProductOrder $order, array $deliveryInfo, array $context): bool
    {
        $method = Str::lower((string) ($deliveryInfo['delivery_method'] ?? ''));
        $methodSlug = Str::slug($method);

        return !empty($deliveryInfo['courier_method'])
            || !empty($deliveryInfo['courier_method_title'])
            || !empty($deliveryInfo['delivery_provider_id'])
            || !empty($deliveryInfo['provider_id'])
            || !empty($deliveryInfo['delivery_employee_id'])
            || !empty($deliveryInfo['outlet_id'])
            || in_array($method, ['home delivery', 'home_delivery', 'store pickup', 'store_pickup'], true)
            || in_array($methodSlug, ['home-delivery', 'store-pickup'], true)
            || $this->deliveryCharge($order, $deliveryInfo, $context) > 0;
    }

    private function initialStatus(array $deliveryInfo): string
    {
        if (!empty($deliveryInfo['delivery_employee_id'])) {
            return 'assigned';
        }

        if (!empty($deliveryInfo['courier_method']) || !empty($deliveryInfo['courier_method_title'])) {
            return 'ready_for_dispatch';
        }

        return 'draft';
    }

    private function deliveryCharge(ProductOrder $order, array $deliveryInfo, array $context): float
    {
        if (isset($context['delivery_charge'])) {
            return (float) $context['delivery_charge'];
        }

        if (isset($deliveryInfo['delivery_charge'])) {
            return (float) $deliveryInfo['delivery_charge'];
        }

        $otherCharges = is_array($order->other_charges)
            ? $order->other_charges
            : (json_decode((string) $order->other_charges, true) ?: []);

        return (float) data_get($otherCharges, 'delivery_charge', $order->delivery_fee ?? 0);
    }

    private function codAmount(ProductOrder $order): float
    {
        $due = (float) ($order->due_amount ?? 0);

        return $due > 0 ? $due : 0;
    }

    private function paymentStatus(ProductOrder $order): string
    {
        $due = (float) ($order->due_amount ?? 0);

        return $due > 0 ? 'pending' : 'paid';
    }

    private function itemQuantity(ProductOrder $order): int
    {
        if ($order->relationLoaded('order_products')) {
            return (int) $order->order_products->sum('qty');
        }

        return (int) DB::table('product_order_products')
            ->where('product_order_id', $order->id)
            ->sum('qty');
    }

    private function makeShipmentCode(ProductOrder $order): string
    {
        do {
            $code = 'DS-' . now()->format('ymd') . '-' . $order->id . '-' . Str::upper(Str::random(4));
        } while (DeliveryShipment::where('shipment_code', $code)->exists());

        return $code;
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('delivery_shipments')
            && Schema::hasTable('delivery_shipment_status_logs');
    }
}
