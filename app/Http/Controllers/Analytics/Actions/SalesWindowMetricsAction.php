<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Models\ProductOrder;
use App\Models\ProductOrderProduct;
use Carbon\Carbon;

/**
 * Aggregates POS sale totals, line-level profit, and delivery fees for delivered/invoiced orders.
 * Order activity date: COALESCE(sale_date, DATE(created_at)) — see raw where below.
 */
class SalesWindowMetricsAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array{
     *     total_sale: float,
     *     profit_from_sale: float,
     *     delivery_total: float
     * }
     */
    public function execute(array $window): array
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();

        // Match RevenueTrendForWindowAction profit logic:
        // Profit: SUM(product_order_products.net_profit) (can be negative)
        $orderDateSql = 'COALESCE(po.sale_date, DATE(po.created_at))';

        $totals = ProductOrder::query()
            ->from('product_orders as po')
            ->where('po.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereRaw("{$orderDateSql} >= ?", [$start])
            ->whereRaw("{$orderDateSql} <= ?", [$end])
            ->selectRaw('COALESCE(SUM(po.total), 0) as total_sale')
            ->selectRaw('COALESCE(SUM(po.delivery_fee), 0) as delivery_total')
            ->first();

        $profitRow = ProductOrderProduct::query()
            ->from('product_order_products as pop')
            ->join('product_orders as po', 'po.id', '=', 'pop.product_order_id')
            ->where('po.status', 'active')
            ->where('pop.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) >= ?', [$start])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) <= ?', [$end])
            ->selectRaw('COALESCE(SUM(pop.net_profit), 0) as profit_from_sale')
            ->first();

        return [
            'total_sale' => (float) ($totals->total_sale ?? 0),
            'profit_from_sale' => (float) ($profitRow->profit_from_sale ?? 0),
            'delivery_total' => (float) ($totals->delivery_total ?? 0),
        ];
    }
}
