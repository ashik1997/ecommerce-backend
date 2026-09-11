<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Http\Controllers\Account\Models\AcMoneyDeposit;
use Carbon\Carbon;

class SumDepositsForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     */
    public function execute(array $window): float
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();

        return (float) AcMoneyDeposit::query()
            ->where('status', 'active')
            ->whereNotNull('deposit_date')
            ->whereBetween('deposit_date', [$start, $end])
            ->sum('amount');
    }
}
