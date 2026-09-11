<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Models\ProductOrderProduct;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Top products by sales amount for a window.
 *
 * Sales = SUM(product_order_products.total_price) for orders (delivered,invoiced).
 * Date bucket filter uses COALESCE(product_orders.sale_date, DATE(product_orders.created_at)).
 */
class TopProductsBySalesForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array<int, array{product_id: int|null, product_name: string, sales: float, qty: int}>
     */
    public function execute(array $window, int $limit = 10): array
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();
        $limit = max(1, min(50, $limit));

        /** @var Collection<int, object{product_id: mixed, product_name: mixed, sales: mixed, qty: mixed}> $rows */
        $rows = ProductOrderProduct::query()
            ->from('product_order_products as pop')
            ->join('product_orders as po', 'po.id', '=', 'pop.product_order_id')
            ->where('po.status', 'active')
            ->where('pop.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) >= ?', [$start])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) <= ?', [$end])
            ->selectRaw('pop.product_id as product_id')
            ->selectRaw('COALESCE(NULLIF(pop.product_name, \'\'), \'Unknown\') as product_name')
            ->selectRaw('COALESCE(SUM(pop.total_price), 0) as sales')
            ->selectRaw('COALESCE(SUM(pop.qty), 0) as qty')
            ->groupBy('pop.product_id', 'product_name')
            ->orderByDesc('sales')
            ->limit($limit)
            ->get();

        return $rows->map(function ($r) {
            return [
                'product_id' => $r->product_id !== null ? (int) $r->product_id : null,
                'product_name' => (string) $r->product_name,
                'sales' => round((float) $r->sales, 2),
                'qty' => (int) $r->qty,
            ];
        })->toArray();
    }
}

