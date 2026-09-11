<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Http\Controllers\Account\Models\AccountIncome;
use Carbon\Carbon;

class SumIncomesForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     */
    public function execute(array $window): float
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();

        return (float) AccountIncome::query()
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', 'active');
            })
            ->whereNotNull('date')
            ->whereBetween('date', [$start, $end])
            ->sum('amount');
    }
}
