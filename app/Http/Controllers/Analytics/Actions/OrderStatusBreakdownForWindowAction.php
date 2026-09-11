<?php

namespace App\Http\Controllers\Analytics\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Counts product_orders by status for the analytics date window.
 * Uses the same activity date as sales KPIs: COALESCE(sale_date, DATE(created_at)).
 */
class OrderStatusBreakdownForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array<int, array{key: string, label: string, count: int, pct: float}>
     */
    public function execute(array $window): array
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();

        $dateSql = 'COALESCE(product_orders.sale_date, DATE(product_orders.created_at))';

        $rows = DB::table('product_orders')
            ->select('order_status', DB::raw('COUNT(*) as c'))
            ->where('product_orders.status', 'active')
            ->whereRaw("{$dateSql} >= ?", [$start])
            ->whereRaw("{$dateSql} <= ?", [$end])
            ->groupBy('order_status')
            ->pluck('c', 'order_status');

        $pending = (int) ($rows['pending'] ?? 0);
        $invoiced = (int) ($rows['invoiced'] ?? 0);
        $delivered = (int) ($rows['delivered'] ?? 0);
        $canceled = (int) (($rows['canceled'] ?? 0) + ($rows['cancelled'] ?? 0));

        $sum = $pending + $invoiced + $delivered + $canceled;
        $denom = max($sum, 1);

        $pct = static function (int $count) use ($denom): float {
            return round(($count / $denom) * 100, 1);
        };

        return [
            ['key' => 'pending', 'label' => 'Pending', 'count' => $pending, 'pct' => $pct($pending)],
            ['key' => 'invoiced', 'label' => 'Invoiced', 'count' => $invoiced, 'pct' => $pct($invoiced)],
            ['key' => 'delivered', 'label' => 'Delivered', 'count' => $delivered, 'pct' => $pct($delivered)],
            ['key' => 'canceled', 'label' => 'Canceled', 'count' => $canceled, 'pct' => $pct($canceled)],
        ];
    }
}
