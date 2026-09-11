<?php

namespace App\Http\Controllers\Courier\Settlement;

use App\Models\ProductOrder;

class CourierStatusNormalizer
{
    public function normalize(ProductOrder $order, ?array $rawResponse = null): array
    {
        $courierInfo = is_array($order->courier_info) ? $order->courier_info : [];
        $raw = $rawResponse ?: $courierInfo;
        $courier = strtolower((string) ($courierInfo['courier'] ?? $raw['courier'] ?? ''));
        $data = $raw['data'] ?? $raw;

        $rawStatus = $this->readStatus($courier, $data, $courierInfo);
        $trackingId = $this->readTrackingId($courier, $data, $courierInfo);
        $codAmount = $this->firstNumeric([
            $data['cod_amount'] ?? null,
            $data['collectable_amount'] ?? null,
            $data['amount_to_collect'] ?? null,
            $courierInfo['cod_amount'] ?? null,
            $courierInfo['collectable_amount'] ?? null,
            $order->total,
        ]);
        $collectedAmount = $this->firstNumeric([
            $data['collected_amount'] ?? null,
            $data['paid_amount'] ?? null,
            $data['received_amount'] ?? null,
            $courierInfo['collected_amount'] ?? null,
        ]);
        $deliveryCost = $this->firstNumeric([
            $data['delivery_fee'] ?? null,
            $data['courier_fee'] ?? null,
            $data['cod_fee'] ?? null,
            $data['charge'] ?? null,
            $courierInfo['delivery_fee'] ?? null,
            $courierInfo['courier_fee'] ?? null,
        ]);

        $normalized = $this->normalizeStatus($courier, $rawStatus);
        $suggestedReceived = $collectedAmount > 0
            ? max(0, $collectedAmount - $deliveryCost)
            : ($normalized === 'success' ? max(0, $codAmount - $deliveryCost) : 0);

        return [
            'courier' => $courier,
            'tracking_id' => $trackingId,
            'raw_status' => $rawStatus,
            'normalized_status' => $normalized,
            'cod_amount' => round($codAmount, 2),
            'collected_amount' => round($collectedAmount, 2),
            'delivery_cost' => round($deliveryCost, 2),
            'suggested_received_amount' => round($suggestedReceived, 2),
            'raw_response' => $raw,
        ];
    }

    protected function readStatus(string $courier, array $data, array $courierInfo): string
    {
        if ($courier === 'carrybee') {
            return (string) ($data['transfer_status'] ?? $data['status'] ?? $courierInfo['status'] ?? 'unknown');
        }

        if ($courier === 'pathao') {
            return (string) ($data['order_status'] ?? $data['status'] ?? $courierInfo['status'] ?? 'unknown');
        }

        return (string) ($data['delivery_status'] ?? $data['status'] ?? $courierInfo['status'] ?? 'unknown');
    }

    protected function readTrackingId(string $courier, array $data, array $courierInfo): ?string
    {
        return $data['consignment_id']
            ?? $data['tracking_code']
            ?? $data['tracking_id']
            ?? $courierInfo['consignment_id']
            ?? $courierInfo['tracking_code']
            ?? null;
    }

    protected function normalizeStatus(string $courier, string $status): string
    {
        $status = strtolower(trim(str_replace(['_', ' '], '-', $status)));

        $success = ['delivered', 'paid', 'completed', 'complete', 'successful', 'success'];
        $partial = ['partial-delivery', 'partial-delivered', 'partial-return', 'exchanged'];
        $returned = ['returned', 'return', 'paid-return', 'full-return'];
        $cancelled = ['delivery-failed', 'failed', 'cancelled', 'canceled', 'pickup-cancelled', 'not-received', 'rejected'];
        $pending = ['pending', 'in-review', 'created', 'updated', 'picked', 'in-transit', 'on-hold', 'assigned-for-delivery', 'at-the-sorting-hub', 'pickup-requested', 'assigned-for-pickup'];

        if (in_array($status, $success, true)) return 'success';
        if (in_array($status, $partial, true)) return 'partial_return';
        if (in_array($status, $returned, true)) return 'full_return';
        if (in_array($status, $cancelled, true)) return 'cancel_not_received';
        if (in_array($status, $pending, true)) return 'pending';

        return 'unknown';
    }

    protected function firstNumeric(array $values): float
    {
        foreach ($values as $value) {
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return 0;
    }
}
