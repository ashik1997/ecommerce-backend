<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Http\Controllers\Account\Models\DbCustomerPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Payment methods breakdown from db_customer_payments.
 *
 * Uses payment_mode (db_paymenttypes.id) and sums db_customer_payments.payment within a date window.
 */
class PaymentMethodsForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array{items: array<int, array{payment_mode: int|null, label: string, amount: float, percentage: float}>}
     */
    public function execute(array $window): array
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();

        /** @var Collection<int, object{payment_mode: mixed, label: mixed, amount: mixed}> $rows */
        $rows = DbCustomerPayment::query()
            ->from('db_customer_payments as cp')
            ->leftJoin('db_paymenttypes as pt', 'pt.id', '=', 'cp.payment_mode')
            ->where('cp.status', 'active')
            ->whereNotNull('cp.payment_date')
            ->whereBetween('cp.payment_date', [$start, $end])
            ->selectRaw('cp.payment_mode as payment_mode')
            ->selectRaw("COALESCE(NULLIF(pt.payment_type, ''), NULLIF(cp.payment_mode_title, ''), 'Unknown') as label")
            ->selectRaw('COALESCE(SUM(cp.payment), 0) as amount')
            ->groupBy('cp.payment_mode', 'label')
            ->orderByDesc('amount')
            ->get();

        $total = (float) $rows->sum(function ($r) {
            return (float) ($r->amount ?? 0);
        });

        $items = $rows->map(function ($r) use ($total) {
            $amount = (float) ($r->amount ?? 0);
            $pct = $total > 0 ? ($amount / $total) * 100 : 0;

            return [
                'payment_mode' => $r->payment_mode !== null ? (int) $r->payment_mode : null,
                'label' => (string) $r->label,
                'amount' => round($amount, 2),
                'percentage' => round($pct, 2),
            ];
        })->values()->toArray();

        return ['items' => $items];
    }
}

