<?php

namespace App\Services\ServiceManagement;

use App\Models\ServiceManagement\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ServiceBillingService
{
    public function calculate(Service $service, array $data, array $products = []): array
    {
        $billingUnitQty = $this->billingUnitQuantity(
            $service,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            (float) ($data['billing_unit_qty'] ?? 1)
        );

        $serviceUnitPrice = (float) ($data['service_unit_price'] ?? $service->base_price ?? 0);
        $serviceSubtotal = round($billingUnitQty * $serviceUnitPrice, 2);
        $normalizedProducts = $this->normalizeProducts($products);
        $productsSubtotal = round($normalizedProducts->sum('total_price'), 2);
        $totalAmount = round($serviceSubtotal + $productsSubtotal, 2);

        return [
            'billing_unit_qty' => $billingUnitQty,
            'service_unit_price' => $serviceUnitPrice,
            'service_subtotal' => $serviceSubtotal,
            'products' => $normalizedProducts,
            'products_subtotal' => $productsSubtotal,
            'total_amount' => $totalAmount,
            'due_amount' => $totalAmount,
        ];
    }

    public function billingUnitQuantity(Service $service, ?string $startDate, ?string $endDate, float $requestedQty): float
    {
        $requestedQty = max($requestedQty, 0.0001);

        if (!in_array($service->billing_unit, ['day', 'month'], true) || !$startDate || !$endDate) {
            return $requestedQty;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            return $requestedQty;
        }

        $days = $start->diffInDays($end) + 1;

        if ($service->billing_unit === 'month') {
            return (float) max(1, ceil($days / 30));
        }

        return (float) max(1, $days);
    }

    private function normalizeProducts(array $products): Collection
    {
        return collect($products)
            ->filter(fn($product) => !empty($product['product_id']) && (float) ($product['quantity_used'] ?? 0) > 0)
            ->map(function ($product) {
                $quantity = (int) $product['quantity_used'];
                $unitPrice = (float) ($product['unit_price'] ?? 0);

                return [
                    'product_id' => (int) $product['product_id'],
                    'quantity_used' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => round($quantity * $unitPrice, 2),
                    'is_required' => !empty($product['is_required']),
                    'note' => $product['note'] ?? null,
                ];
            })
            ->values();
    }
}
