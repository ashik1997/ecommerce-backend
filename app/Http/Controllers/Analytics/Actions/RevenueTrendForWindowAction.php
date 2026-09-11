<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Models\ProductOrderProduct;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Daily revenue & profit trend based on ProductOrderProduct snapshots.
 *
 * Revenue: SUM(product_order_products.total_price)
 * Profit:  SUM(product_order_products.net_profit)
 *
 * Includes orders: order_status IN ('delivered','invoiced')
 * Activity date: COALESCE(product_orders.sale_date, DATE(product_orders.created_at))
 */
class RevenueTrendForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array{labels: array<int, string>, revenue: array<int, float>, profit: array<int, float>}
     */
    public function execute(array $window): array
    {
        $startDate = $window['start']->toDateString();
        $endDate = $window['end']->toDateString();

        $popTable = (new ProductOrderProduct())->getTable();

        /** @var Collection<int, object{d: string, revenue: mixed, profit: mixed}> $rows */
        $rows = ProductOrderProduct::query()
            ->from("{$popTable} as pop")
            ->join('product_orders as po', 'po.id', '=', 'pop.product_order_id')
            ->where('po.status', 'active')
            ->where('pop.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) >= ?', [$startDate])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) <= ?', [$endDate])
            ->selectRaw('COALESCE(po.sale_date, DATE(po.created_at)) as d')
            ->selectRaw('COALESCE(SUM(pop.total_price), 0) as revenue')
            ->selectRaw('COALESCE(SUM(pop.net_profit), 0) as profit')
            ->groupBy('d')
            ->orderBy('d', 'asc')
            ->get();

        $byDate = [];
        foreach ($rows as $row) {
            $byDate[(string) $row->d] = [
                'revenue' => (float) $row->revenue,
                'profit' => (float) $row->profit,
            ];
        }

        $labels = [];
        $revenue = [];
        $profit = [];

        $cursor = $window['start']->copy()->startOfDay();
        $end = $window['end']->copy()->startOfDay();
        while ($cursor->lessThanOrEqualTo($end)) {
            $d = $cursor->toDateString();
            $labels[] = $d;
            $revenue[] = round((float) ($byDate[$d]['revenue'] ?? 0), 2);
            $profit[] = round((float) ($byDate[$d]['profit'] ?? 0), 2);
            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'profit' => $profit,
        ];
    }

    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     */
    public function sumRevenue(array $window): float
    {
        $startDate = $window['start']->toDateString();
        $endDate = $window['end']->toDateString();

        $popTable = (new ProductOrderProduct())->getTable();

        return (float) ProductOrderProduct::query()
            ->from("{$popTable} as pop")
            ->join('product_orders as po', 'po.id', '=', 'pop.product_order_id')
            ->where('po.status', 'active')
            ->where('pop.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) >= ?', [$startDate])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) <= ?', [$endDate])
            ->sum('pop.total_price');
    }
}

