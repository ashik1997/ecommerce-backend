<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Models\XRegularVisitor;
use Carbon\Carbon;

class WebsiteVisitorsForWindowAction
{
    /**
     * Counts total visits for the window.
     * Uses SUM(COALESCE(qty, 1)) so rows without qty still count as 1.
     *
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return float
     */
    public function execute(array $window): float
    {
        $start = $window['start']->copy()->startOfDay();
        $end = $window['end']->copy()->endOfDay();

        return (float) XRegularVisitor::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(COALESCE(qty, 1)), 0) as total_visits')
            ->value('total_visits');
    }
}

