<?php

namespace App\Http\Controllers\Analytics\Actions;

use Carbon\Carbon;

/**
 * Builds the at-a-glance KPI array for current vs previous windows.
 * Net profit: profit_from_sale + manual_incomes - expenses - delivery_total (deposits excluded).
 */
class ComposeAtAGlanceKpisAction
{
    private const EPS = 0.0001;

    public function __construct(
        protected ResolveAnalyticsWindowsAction $resolveWindows,
        protected SalesWindowMetricsAction $salesWindow,
        protected SumIncomesForWindowAction $sumIncomes,
        protected SumDepositsForWindowAction $sumDeposits,
        protected SumExpensesForWindowAction $sumExpenses,
        protected OrderStatusBreakdownForWindowAction $orderStatusBreakdown,
    ) {}

    /**
     * @param  array{preset?: string, from?: string|null, to?: string|null}  $input
     * @return array{
     *     windows: array,
     *     kpis: array<int, array{title: string, value: float, compared_trend: string, compared_value: float, compared_percentage: float|null}>
     * }
     */
    public function execute(array $input): array
    {
        $resolved = $this->resolveWindows->execute($input);
        $current = $resolved['current'];
        $previous = $resolved['previous'];

        $curSales = $this->salesWindow->execute($current);
        $prevSales = $this->salesWindow->execute($previous);

        $curIncomes = $this->sumIncomes->execute($current);
        $prevIncomes = $this->sumIncomes->execute($previous);

        $curDeposits = $this->sumDeposits->execute($current);
        $prevDeposits = $this->sumDeposits->execute($previous);

        $curExpenses = $this->sumExpenses->execute($current);
        $prevExpenses = $this->sumExpenses->execute($previous);

        $curNet = $this->netProfit($curSales, $curIncomes, $curExpenses);
        $prevNet = $this->netProfit($prevSales, $prevIncomes, $prevExpenses);

        $windows = $this->serializeWindows($resolved, $current, $previous);

        $kpis = [
            $this->comparedKpi('total_sale', $curSales['total_sale'], $prevSales['total_sale']),
            $this->comparedKpi('profit_from_sale', $curSales['profit_from_sale'], $prevSales['profit_from_sale']),
            $this->comparedKpi('manual_incomes', $curIncomes, $prevIncomes),
            $this->comparedKpi('deposits', $curDeposits, $prevDeposits),
            $this->comparedKpi('expenses', $curExpenses, $prevExpenses),
            $this->comparedKpi('delivery_cost', $curSales['delivery_total'], $prevSales['delivery_total']),
            $this->comparedKpi('net_profit', $curNet, $prevNet),
        ];

        $orderStatus = $this->orderStatusBreakdown->execute($current);

        return [
            'windows' => $windows,
            'kpis' => $kpis,
            'order_status' => $orderStatus,
        ];
    }

    /**
     * @param  array<string, mixed>  $resolved
     */
    protected function serializeWindows(array $resolved, array $current, array $previous): array
    {
        return [
            'preset' => $resolved['preset'],
            'preset_label' => $resolved['preset_label'],
            'current' => [
                'start' => $current['start']->toIso8601String(),
                'end' => $current['end']->toIso8601String(),
                'start_date' => $current['start']->toDateString(),
                'end_date' => $current['end']->toDateString(),
            ],
            'previous' => [
                'start' => $previous['start']->toIso8601String(),
                'end' => $previous['end']->toIso8601String(),
                'start_date' => $previous['start']->toDateString(),
                'end_date' => $previous['end']->toDateString(),
            ],
        ];
    }

    /**
     * @param  array{profit_from_sale: float, delivery_total: float}  $sales
     */
    protected function netProfit(array $sales, float $incomes, float $expenses): float
    {
        return (float) $sales['profit_from_sale']
            + $incomes
            - $expenses
            - (float) $sales['delivery_total'];
    }

    /**
     * @return array{title: string, value: float, compared_trend: string, compared_value: float, compared_percentage: float|null}
     */
    protected function comparedKpi(string $title, float $value, float $comparedValue): array
    {
        $trend = 'flat';
        if ($value > $comparedValue + self::EPS) {
            $trend = 'up';
        } elseif ($value < $comparedValue - self::EPS) {
            $trend = 'down';
        }

        $percentage = $this->comparedPercentage($value, $comparedValue);

        return [
            'title' => $title,
            'value' => round($value, 2),
            'compared_trend' => $trend,
            'compared_value' => round($comparedValue, 2),
            'compared_percentage' => $percentage,
        ];
    }

    protected function comparedPercentage(float $value, float $comparedValue): ?float
    {
        if (abs($comparedValue) < self::EPS) {
            if ($value > self::EPS) {
                return 100.0;
            }

            return 0.0;
        }

        return round((($value - $comparedValue) / $comparedValue) * 100, 2);
    }
}
