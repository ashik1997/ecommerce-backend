<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Models\ProductOrderProduct;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Top categories by number of orders in a window.
 *
 * Counts DISTINCT product_orders.id per category from product_order_products lines.
 * Filters orders: order_status IN (delivered,invoiced), status=active.
 * Date filter uses COALESCE(product_orders.sale_date, DATE(product_orders.created_at)).
 */
class TopCategoriesByOrdersForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array<int, array{category_id: int|null, category_name: string, total_orders: int}>
     */
    public function execute(array $window, int $limit = 5): array
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();
        $limit = max(1, min(50, $limit));

        /** @var Collection<int, object{category_id: mixed, category_name: mixed, total_orders: mixed}> $rows */
        $rows = ProductOrderProduct::query()
            ->from('product_order_products as pop')
            ->join('product_orders as po', 'po.id', '=', 'pop.product_order_id')
            ->join('products as p', 'p.id', '=', 'pop.product_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->where('po.status', 'active')
            ->where('pop.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) >= ?', [$start])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) <= ?', [$end])
            ->selectRaw('p.category_id as category_id')
            ->selectRaw("COALESCE(NULLIF(c.name,''), 'Uncategorized') as category_name")
            ->selectRaw('COUNT(DISTINCT po.id) as total_orders')
            ->groupBy('p.category_id', 'category_name')
            ->orderByDesc('total_orders')
            ->limit($limit)
            ->get();

        return $rows->map(function ($r) {
            return [
                'category_id' => $r->category_id !== null ? (int) $r->category_id : null,
                'category_name' => (string) $r->category_name,
                'total_orders' => (int) $r->total_orders,
            ];
        })->toArray();
    }
}

