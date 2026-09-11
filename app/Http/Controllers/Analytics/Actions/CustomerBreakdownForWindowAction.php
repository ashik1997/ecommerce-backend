<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Models\ProductOrder;
use Carbon\Carbon;

class CustomerBreakdownForWindowAction
{
    /**
     * "New" means customer whose first (delivered/invoiced) order date falls inside the window.
     * "Returning" means customer with at least one (delivered/invoiced) order inside the window
     * but first order date is before the window.
     *
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array{
     *   new: array{count:int, pct:float},
     *   returning: array{count:int, pct:float},
     *   total: int
     * }
     */
    public function execute(array $window): array
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();

        // One row per customer that has at least one order in the window.
        $rows = ProductOrder::query()
            ->from('product_orders as po')
            ->where('po.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereNotNull('po.customer_id')
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) >= ?', [$start])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) <= ?', [$end])
            ->groupBy('po.customer_id')
            ->selectRaw('po.customer_id as customer_id')
            ->selectRaw('MIN(COALESCE(po.sale_date, DATE(po.created_at))) as first_order_date_in_window')
            ->get();

        $customerIds = $rows->pluck('customer_id')->filter()->values()->all();
        $total = count($customerIds);

        if ($total === 0) {
            return [
                'new' => ['count' => 0, 'pct' => 0.0],
                'returning' => ['count' => 0, 'pct' => 0.0],
                'total' => 0,
            ];
        }

        // Fetch each customer's first-ever order date (for the relevant customers only).
        // This is still a single query, grouped by customer_id.
        $firstEver = ProductOrder::query()
            ->from('product_orders as po')
            ->where('po.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereIn('po.customer_id', $customerIds)
            ->groupBy('po.customer_id')
            ->selectRaw('po.customer_id as customer_id')
            ->selectRaw('MIN(COALESCE(po.sale_date, DATE(po.created_at))) as first_ever_date')
            ->pluck('first_ever_date', 'customer_id');

        $new = 0;
        $returning = 0;

        foreach ($customerIds as $cid) {
            $first = (string) ($firstEver[$cid] ?? '');
            if ($first !== '' && $first >= $start && $first <= $end) {
                $new += 1;
            } else {
                $returning += 1;
            }
        }

        $den = max($total, 1);
        return [
            'new' => ['count' => $new, 'pct' => round(($new / $den) * 100, 2)],
            'returning' => ['count' => $returning, 'pct' => round(($returning / $den) * 100, 2)],
            'total' => $total,
        ];
    }
}

