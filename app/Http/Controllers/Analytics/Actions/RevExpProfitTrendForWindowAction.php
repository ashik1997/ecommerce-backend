<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Http\Controllers\Account\Models\DbExpense;
use App\Models\ProductOrderProduct;
use Carbon\Carbon;

/**
 * Daily Sales vs Expenses vs Profit.
 *
 * Sales:         SUM(product_order_products.total_price) for orders (delivered,invoiced)
 * Expenses:      SUM(db_expenses.expense_amt)
 * Profit:        SUM(product_order_products.net_profit) - expenses
 *
 * Sales date bucket: COALESCE(product_orders.sale_date, DATE(product_orders.created_at))
 * Expense date bucket: db_expenses.expense_date
 */
class RevExpProfitTrendForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array{labels: array<int, string>, sales: array<int, float>, expenses: array<int, float>, profit: array<int, float>}
     */
    public function execute(array $window): array
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();

        // Sales and sale profit grouped by date (from orders).
        $salesRows = ProductOrderProduct::query()
            ->from('product_order_products as pop')
            ->join('product_orders as po', 'po.id', '=', 'pop.product_order_id')
            ->where('po.status', 'active')
            ->where('pop.status', 'active')
            ->whereIn('po.order_status', ['delivered', 'invoiced'])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) >= ?', [$start])
            ->whereRaw('COALESCE(po.sale_date, DATE(po.created_at)) <= ?', [$end])
            ->selectRaw('COALESCE(po.sale_date, DATE(po.created_at)) as d')
            ->selectRaw('COALESCE(SUM(pop.total_price), 0) as sales')
            ->selectRaw('COALESCE(SUM(pop.net_profit), 0) as profit_from_sale')
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        // Expenses grouped by expense_date.
        $expRows = DbExpense::query()
            ->where('status', 'active')
            ->whereNotNull('expense_date')
            ->whereBetween('expense_date', [$start, $end])
            ->selectRaw('expense_date as d')
            ->selectRaw('COALESCE(SUM(expense_amt), 0) as expenses')
            ->groupBy('d')
            ->pluck('expenses', 'd')
            ->toArray();

        $labels = [];
        $sales = [];
        $expenses = [];
        $profit = [];

        $cursor = $window['start']->copy()->startOfDay();
        $endCursor = $window['end']->copy()->startOfDay();
        while ($cursor->lessThanOrEqualTo($endCursor)) {
            $d = $cursor->toDateString();
            $saleRow = $salesRows->get($d);
            $s = (float) ($saleRow->sales ?? 0);
            $profitFromSale = (float) ($saleRow->profit_from_sale ?? 0);
            $exp = (float) ($expRows[$d] ?? 0);

            $labels[] = $d;
            $sales[] = round($s, 2);
            $expenses[] = round($exp, 2);
            $profit[] = round($profitFromSale - $exp, 2);

            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'sales' => $sales,
            'expenses' => $expenses,
            'profit' => $profit,
        ];
    }
}

