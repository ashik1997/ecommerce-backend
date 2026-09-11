<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Models\Product;
use App\Models\ProductOrderProduct;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Trending products (top sold) within a window.
 *
 * total_sold   = SUM(product_order_products.qty)
 * total_sales  = SUM(product_order_products.total_price)
 *
 * Orders filter: order_status IN (delivered,invoiced), status=active
 * Date filter: COALESCE(product_orders.sale_date, DATE(product_orders.created_at))
 */
class TrendingProductsForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array{
     *   data: array<int, array{product_id:int|null, name:string, price:float, discount_price:float, stock:float, total_sold:int, total_sales:float}>,
     *   pagination: array{current_page:int, last_page:int, per_page:int, total:int}
     * }
     */
    public function execute(array $window, int $page = 1, int $perPage = 10): array
    {
        $page = max(1, (int) $page);
        $perPage = max(1, min(50, (int) $perPage));

        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();

        $pTable = (new Product())->getTable();

        $q = ProductOrderProduct::query()
            ->from('product_order_products as pop')
            ->join('product_orders as po', 'po.id', '=', 'pop.product_order_id')
            ->join("{$pTable} as p", 'p.id', '=', 'pop.product_id')
            ->where('po.status', 'active')
            ->where('pop.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) >= ?', [$start])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) <= ?', [$end])
            ->selectRaw('p.id as product_id')
            ->selectRaw('p.name as name')
            ->selectRaw('COALESCE(p.price, 0) as price')
            ->selectRaw('COALESCE(p.discount_price, 0) as discount_price')
            ->selectRaw('COALESCE(p.stock, 0) as stock')
            ->selectRaw('COALESCE(SUM(pop.qty), 0) as total_sold')
            ->selectRaw('COALESCE(SUM(pop.total_price), 0) as total_sales')
            ->groupBy('p.id', 'p.name', 'p.price', 'p.discount_price', 'p.stock')
            ->orderByDesc('total_sold')
            ->orderByDesc('total_sales');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $q->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(function ($r) {
            return [
                'product_id' => $r->product_id !== null ? (int) $r->product_id : null,
                'name' => (string) $r->name,
                'price' => (float) ($r->price ?? 0),
                'discount_price' => (float) ($r->discount_price ?? 0),
                'stock' => (float) ($r->stock ?? 0),
                'total_sold' => (int) ($r->total_sold ?? 0),
                'total_sales' => round((float) ($r->total_sales ?? 0), 2),
            ];
        })->values()->toArray();

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => (int) $paginator->currentPage(),
                'last_page' => (int) $paginator->lastPage(),
                'per_page' => (int) $paginator->perPage(),
                'total' => (int) $paginator->total(),
            ],
        ];
    }
}

