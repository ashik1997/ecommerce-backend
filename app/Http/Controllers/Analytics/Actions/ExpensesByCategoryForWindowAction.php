<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Http\Controllers\Account\Models\DbExpense;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ExpensesByCategoryForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array{items: array<int, array{category_id:int|null, category_name:string, expense:float, pct:float}>}
     */
    public function execute(array $window, int $limit = 10): array
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();
        $limit = max(1, min(50, $limit));

        /** @var Collection<int, object{category_id:mixed, category_name:mixed, expense:mixed}> $rows */
        $rows = DbExpense::query()
            ->from('db_expenses as e')
            ->leftJoin('db_expense_categories as c', 'c.id', '=', 'e.category_id')
            ->where('e.status', 'active')
            ->whereBetween('e.expense_date', [$start, $end])
            ->selectRaw('e.category_id as category_id')
            ->selectRaw("COALESCE(NULLIF(c.category_name,''), 'Uncategorized') as category_name")
            ->selectRaw('COALESCE(SUM(e.expense_amt), 0) as expense')
            ->groupBy('e.category_id', 'category_name')
            ->orderByDesc('expense')
            ->limit($limit)
            ->get();

        $total = (float) $rows->sum(function ($r) {
            return (float) ($r->expense ?? 0);
        });

        $items = $rows->map(function ($r) use ($total) {
            $expense = (float) ($r->expense ?? 0);
            $pct = $total > 0 ? ($expense / $total) * 100 : 0;

            return [
                'category_id' => $r->category_id !== null ? (int) $r->category_id : null,
                'category_name' => (string) $r->category_name,
                'expense' => round($expense, 2),
                'pct' => round($pct, 2),
            ];
        })->values()->toArray();

        return ['items' => $items];
    }
}

