<?php

namespace App\Http\Controllers\Analytics\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyProfitLossForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array{items: array<int, array<string, mixed>>}
     */
    public function execute(array $window): array
    {
        $items = [];
        $cursor = $window['start']->copy()->startOfMonth();
        $lastMonth = $window['end']->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($lastMonth)) {
            $monthStart = $cursor->copy()->startOfMonth();
            if ($monthStart->lessThan($window['start'])) {
                $monthStart = $window['start']->copy()->startOfDay();
            }

            $monthEnd = $cursor->copy()->endOfMonth();
            if ($monthEnd->greaterThan($window['end'])) {
                $monthEnd = $window['end']->copy()->endOfDay();
            }

            $items[] = $this->rowForWindow($monthStart, $monthEnd);
            $cursor->addMonthNoOverflow();
        }

        return ['items' => array_reverse($items)];
    }

    /**
     * @return array<string, mixed>
     */
    private function rowForWindow(Carbon $start, Carbon $end): array
    {
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $lineAgg = DB::table('product_order_products as pop')
            ->join('product_orders as po', 'po.id', '=', 'pop.product_order_id')
            ->where('po.status', 'active')
            ->where('pop.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) >= ?', [$startDate])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) <= ?', [$endDate])
            ->selectRaw('COALESCE(SUM(COALESCE(pop.qty, 0) * COALESCE(pop.sale_price, 0)), 0) as gross_sales')
            ->selectRaw('COALESCE(SUM(pop.total_price), 0) as revenue')
            ->selectRaw('COALESCE(SUM(COALESCE(pop.qty, 0) * COALESCE(pop.purchase_price, 0)), 0) as direct_costs')
            ->first();

        $orderAgg = DB::table('product_orders')
            ->where('product_orders.status', 'active')
            ->whereIn('product_orders.order_status', ['delivered', 'invoiced'])
            ->whereRaw('COALESCE(product_orders.sale_date, DATE(product_orders.created_at)) >= ?', [$startDate])
            ->whereRaw('COALESCE(product_orders.sale_date, DATE(product_orders.created_at)) <= ?', [$endDate])
            ->selectRaw('COALESCE(SUM(product_orders.total), 0) as total_sales')
            ->selectRaw('COALESCE(SUM(product_orders.delivery_fee), 0) as delivery_total')
            ->selectRaw('COALESCE(SUM(COALESCE(product_orders.calculated_discount_amount, 0) + COALESCE(product_orders.coupon_discount_amount, 0)), 0) as order_discounts')
            ->first();

        $returns = (float) DB::table('product_order_returns as por')
            ->join('product_orders as po', 'po.id', '=', 'por.product_order_id')
            ->where('por.status', 'active')
            ->where('por.return_status', 'approved')
            ->where('po.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) >= ?', [$startDate])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) <= ?', [$endDate])
            ->sum('por.total');

        $operatingExpenses = (float) DB::table('db_expenses')
            ->where('status', 'active')
            ->whereNotNull('expense_date')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('expense_amt');

        $manualIncomes = (float) DB::table('ac_incomes')
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', 'active');
            })
            ->whereNotNull('date')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $grossSales = (float) ($lineAgg->gross_sales ?? 0);
        $revenue = (float) ($lineAgg->revenue ?? 0);
        $directCosts = (float) ($lineAgg->direct_costs ?? 0);
        $lineDiscounts = max(0, $grossSales - $revenue);
        $discounts = $lineDiscounts + (float) ($orderAgg->order_discounts ?? 0);
        $netSales = max(0, $grossSales - $discounts - $returns);
        $totalSales = max(0, (float) ($orderAgg->total_sales ?? 0) - $returns);
        $grossProfit = $netSales - $directCosts;
        $netProfit = $grossProfit + $manualIncomes - $operatingExpenses - (float) ($orderAgg->delivery_total ?? 0);

        return [
            'month' => $start->format('M Y'),
            'month_key' => $start->format('Y-m'),
            'gross_sales' => $this->money($grossSales),
            'discounts' => $this->money($discounts),
            'returns' => $this->money($returns),
            'net_sales' => $this->money($netSales),
            'total_sales' => $this->money($totalSales),
            'revenue' => $this->money($revenue),
            'direct_costs' => $this->money($directCosts),
            'gross_profit' => $this->money($grossProfit),
            'gross_margin' => $this->pct($grossProfit, $revenue),
            'operating_expenses' => $this->money($operatingExpenses),
            'net_profit' => $this->money($netProfit),
            'net_margin' => $this->pct($netProfit, $revenue),
        ];
    }

    private function money(float $value): float
    {
        return round($value, 2);
    }

    private function pct(float $value, float $base): float
    {
        if (abs($base) < 0.0001) {
            return 0.0;
        }

        return round(($value / $base) * 100, 2);
    }
}
