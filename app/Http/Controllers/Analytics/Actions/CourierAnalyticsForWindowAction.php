<?php

namespace App\Http\Controllers\Analytics\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates courier metrics for Pathao, Steadfast, and CarryBee from product_orders.
 * Requires is_couriered = 1; courier slug from JSON courier_info.courier (lowercase).
 * Status buckets use courier_info.status with non-overlapping rules; total_pending is the remainder.
 */
class CourierAnalyticsForWindowAction
{
    /** @var list<string> */
    public const COURIER_SLUGS = ['pathao', 'steadfast', 'carrybee'];

    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array<int, array<string, mixed>>
     */
    public function execute(array $window): array
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();

        $dateSql = 'COALESCE(product_orders.sale_date, DATE(product_orders.created_at))';
        $courierSlug = 'LOWER(JSON_UNQUOTE(JSON_EXTRACT(product_orders.courier_info, \'$.courier\')))';
        $courierStatus = 'LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(product_orders.courier_info, \'$.status\')), \'\'))';

        $cancelledCond = "({$courierStatus} IN ('pickup-cancelled','pickup-failed','delivery-failed'))";
        $returnedCond = "(NOT {$cancelledCond} AND ({$courierStatus} IN ('returned','paid-return') OR product_orders.is_returned = 1))";
        $deliveredCond = "(NOT {$cancelledCond} AND {$courierStatus} NOT IN ('returned','paid-return') AND product_orders.is_returned = 0 AND {$courierStatus} IN ('delivered','partial-delivery'))";

        $rows = DB::table('product_orders')
            ->selectRaw("{$courierSlug} as courier_name")
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw("SUM(CASE WHEN {$cancelledCond} THEN 1 ELSE 0 END) as total_cancelled")
            ->selectRaw("SUM(CASE WHEN {$returnedCond} THEN 1 ELSE 0 END) as total_returned")
            ->selectRaw("SUM(CASE WHEN {$deliveredCond} THEN 1 ELSE 0 END) as total_delivered")
            ->selectRaw('SUM(COALESCE(product_orders.delivery_fee, 0)) as total_fee')
            ->where('product_orders.status', 'active')
            ->where('product_orders.is_couriered', 1)
            ->whereNotNull('product_orders.courier_info')
            ->whereRaw("{$dateSql} >= ?", [$start])
            ->whereRaw("{$dateSql} <= ?", [$end])
            ->whereRaw("{$courierSlug} IN ('pathao','steadfast','carrybee')")
            ->groupBy(DB::raw($courierSlug))
            ->get();

        $bySlug = [];
        foreach ($rows as $row) {
            $slug = (string) $row->courier_name;
            $totalOrders = (int) $row->total_orders;
            $cancelled = (int) $row->total_cancelled;
            $returned = (int) $row->total_returned;
            $delivered = (int) $row->total_delivered;
            $pending = max(0, $totalOrders - $cancelled - $returned - $delivered);

            $bySlug[$slug] = [
                'courier_name' => $slug,
                'total_orders' => $totalOrders,
                'total_delivered' => $delivered,
                'total_pending' => $pending,
                'total_cancelled' => $cancelled,
                'total_returned' => $returned,
                'total_fee' => round((float) $row->total_fee, 2),
            ];
        }

        $out = [];
        foreach (self::COURIER_SLUGS as $slug) {
            $out[] = $bySlug[$slug] ?? [
                'courier_name' => $slug,
                'total_orders' => 0,
                'total_delivered' => 0,
                'total_pending' => 0,
                'total_cancelled' => 0,
                'total_returned' => 0,
                'total_fee' => 0.0,
            ];
        }

        return $out;
    }
}
