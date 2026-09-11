<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Models\ProductOrderProduct;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Profit share by category (top N) for the window.
 *
 * Profit = SUM(product_order_products.net_profit) for delivered/invoiced orders.
 * Date filter uses COALESCE(product_orders.sale_date, DATE(product_orders.created_at)).
 */
class CategoryProfitShareForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array{items: array<int, array{category_id:int|null, category_name:string, profit:float, pct:float}>}
     */
    public function execute(array $window, int $limit = 10): array
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();
        $limit = max(1, min(50, $limit));

        /** @var Collection<int, object{category_id:mixed, category_name:mixed, profit:mixed}> $rows */
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
            ->selectRaw('COALESCE(SUM(pop.net_profit), 0) as profit')
            ->groupBy('p.category_id', 'category_name')
            ->orderByDesc('profit')
            ->limit($limit)
            ->get();

        $total = (float) $rows->sum(function ($r) {
            return (float) ($r->profit ?? 0);
        });

        $items = $rows->map(function ($r) use ($total) {
            $profit = (float) ($r->profit ?? 0);
            $pct = $total > 0 ? ($profit / $total) * 100 : 0;

            return [
                'category_id' => $r->category_id !== null ? (int) $r->category_id : null,
                'category_name' => (string) $r->category_name,
                'profit' => round($profit, 2),
                'pct' => round($pct, 2),
            ];
        })->values()->toArray();

        return ['items' => $items];
    }
}

