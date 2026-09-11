<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Models\ProductOrder;
use App\Models\ProductOrderProduct;
use Carbon\Carbon;

/**
 * Daily order count vs profit within a window.
 *
 * Orders count: COUNT(DISTINCT product_orders.id) for order_status IN (delivered,invoiced)
 * Profit:       SUM(product_order_products.net_profit) for the same orders
 *
 * Date bucket: COALESCE(product_orders.sale_date, DATE(product_orders.created_at))
 */
class OrderCountVsProfitForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array{labels: array<int, string>, orders: array<int, int>, profit: array<int, float>}
     */
    public function execute(array $window): array
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();

        // Orders count grouped by date.
        $ordersByDate = ProductOrder::query()
            ->where('status', 'active')
            ->whereIn('order_status', ['delivered', 'invoiced'])
            ->whereRaw('COALESCE(sale_date, DATE(created_at)) >= ?', [$start])
            ->whereRaw('COALESCE(sale_date, DATE(created_at)) <= ?', [$end])
            ->selectRaw('COALESCE(sale_date, DATE(created_at)) as d')
            ->selectRaw('COUNT(DISTINCT id) as orders')
            ->groupBy('d')
            ->pluck('orders', 'd')
            ->toArray();

        // Profit grouped by date.
        $profitByDate = ProductOrderProduct::query()
            ->from('product_order_products as pop')
            ->join('product_orders as po', 'po.id', '=', 'pop.product_order_id')
            ->where('po.status', 'active')
            ->where('pop.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) >= ?', [$start])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) <= ?', [$end])
            ->selectRaw('COALESCE(po.sale_date, DATE(po.created_at)) as d')
            ->selectRaw('COALESCE(SUM(pop.net_profit), 0) as profit')
            ->groupBy('d')
            ->pluck('profit', 'd')
            ->toArray();

        $labels = [];
        $orders = [];
        $profit = [];

        $cursor = $window['start']->copy()->startOfDay();
        $endCursor = $window['end']->copy()->startOfDay();
        while ($cursor->lessThanOrEqualTo($endCursor)) {
            $d = $cursor->toDateString();
            $labels[] = $d;
            $orders[] = (int) ($ordersByDate[$d] ?? 0);
            $profit[] = round((float) ($profitByDate[$d] ?? 0), 2);
            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'orders' => $orders,
            'profit' => $profit,
        ];
    }
}

