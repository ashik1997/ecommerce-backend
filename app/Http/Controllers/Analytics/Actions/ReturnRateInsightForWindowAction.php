<?php

namespace App\Http\Controllers\Analytics\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Return-rate insight for delivered/invoiced orders in the selected analytics window.
 *
 * Order window filter uses COALESCE(product_orders.sale_date, DATE(product_orders.created_at)).
 */
class ReturnRateInsightForWindowAction
{
    private const TARGET_RATE = 8.0;

    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array{breakdown: array<string, mixed>}
     */
    public function execute(array $window): array
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();

        $orderDateSql = 'COALESCE(product_orders.sale_date, DATE(product_orders.created_at))';
        $aliasedOrderDateSql = 'COALESCE(po.sale_date, DATE(po.created_at))';

        $totalOrders = (int) DB::table('product_orders')
            ->where('product_orders.status', 'active')
            ->whereIn('product_orders.order_status', ['delivered', 'invoiced'])
            ->whereRaw("{$orderDateSql} >= ?", [$start])
            ->whereRaw("{$orderDateSql} <= ?", [$end])
            ->count();

        $returnSummary = DB::table('product_order_returns as por')
            ->join('product_orders as po', 'po.id', '=', 'por.product_order_id')
            ->where('por.status', 'active')
            ->where('por.return_status', 'approved')
            ->where('po.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereRaw("{$aliasedOrderDateSql} >= ?", [$start])
            ->whereRaw("{$aliasedOrderDateSql} <= ?", [$end])
            ->selectRaw('COUNT(DISTINCT por.product_order_id) as returned_orders')
            ->selectRaw('COUNT(DISTINCT por.id) as return_count')
            ->selectRaw('COALESCE(SUM(por.total), 0) as returned_value')
            ->first();

        $returnedOrders = (int) ($returnSummary->returned_orders ?? 0);
        $returnCount = (int) ($returnSummary->return_count ?? 0);
        $returnedValue = (float) ($returnSummary->returned_value ?? 0);
        $rate = $totalOrders > 0 ? ($returnedOrders / $totalOrders) * 100 : 0;

        $topCategory = DB::table('product_order_return_products as porp')
            ->join('product_order_returns as por', 'por.id', '=', 'porp.product_order_return_id')
            ->join('product_orders as po', 'po.id', '=', 'por.product_order_id')
            ->leftJoin('products as p', 'p.id', '=', 'porp.product_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->where('porp.status', 'active')
            ->where('por.status', 'active')
            ->where('por.return_status', 'approved')
            ->where('po.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereRaw("{$aliasedOrderDateSql} >= ?", [$start])
            ->whereRaw("{$aliasedOrderDateSql} <= ?", [$end])
            ->selectRaw('p.category_id as category_id')
            ->selectRaw("COALESCE(NULLIF(c.name,''), 'Uncategorized') as category_name")
            ->selectRaw('COALESCE(SUM(porp.qty), 0) as returned_qty')
            ->selectRaw('COALESCE(SUM(porp.total_price), 0) as returned_value')
            ->selectRaw('COUNT(DISTINCT por.id) as return_count')
            ->groupBy('p.category_id', 'category_name')
            ->orderByDesc('returned_qty')
            ->orderByDesc('returned_value')
            ->first();

        $status = 'ok';
        if ($rate > self::TARGET_RATE) {
            $status = 'alert';
        } elseif ($rate >= self::TARGET_RATE * 0.75) {
            $status = 'warning';
        }

        return [
            'breakdown' => [
                'rate' => round($rate, 2),
                'target' => self::TARGET_RATE,
                'status' => $status,
                'total_orders' => $totalOrders,
                'returned_orders' => $returnedOrders,
                'return_count' => $returnCount,
                'returned_value' => round($returnedValue, 2),
                'top_category' => [
                    'category_id' => $topCategory && $topCategory->category_id !== null ? (int) $topCategory->category_id : null,
                    'category_name' => $topCategory ? (string) $topCategory->category_name : 'No returns',
                    'returned_qty' => $topCategory ? (int) $topCategory->returned_qty : 0,
                    'returned_value' => $topCategory ? round((float) $topCategory->returned_value, 2) : 0.0,
                    'return_count' => $topCategory ? (int) $topCategory->return_count : 0,
                ],
            ],
        ];
    }
}
