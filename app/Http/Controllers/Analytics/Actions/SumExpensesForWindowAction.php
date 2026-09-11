<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Http\Controllers\Account\Models\DbExpense;
use Carbon\Carbon;

class SumExpensesForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     */
    public function execute(array $window): float
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();

        return (float) DbExpense::query()
            ->where('status', 'active')
            ->whereNotNull('expense_date')
            ->whereBetween('expense_date', [$start, $end])
            ->sum('expense_amt');
    }
}
