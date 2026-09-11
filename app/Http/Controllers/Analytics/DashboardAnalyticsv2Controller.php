<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Analytics\Actions\ComposeAtAGlanceKpisAction;
use App\Http\Controllers\Analytics\Actions\CategoryProfitShareForWindowAction;
use App\Http\Controllers\Analytics\Actions\CustomerBreakdownForWindowAction;
use App\Http\Controllers\Analytics\Actions\ExpensesByCategoryForWindowAction;
use App\Http\Controllers\Analytics\Actions\WebsiteVisitorsForWindowAction;
use App\Http\Controllers\Analytics\Actions\CourierAnalyticsForWindowAction;
use App\Http\Controllers\Analytics\Actions\MonthlyProfitLossForWindowAction;
use App\Http\Controllers\Analytics\Actions\QuotationFunnelForWindowAction;
use App\Http\Controllers\Analytics\Actions\RevenueTrendForWindowAction;
use App\Http\Controllers\Analytics\Actions\RevExpProfitTrendForWindowAction;
use App\Http\Controllers\Analytics\Actions\ResolveAnalyticsWindowsAction;
use App\Http\Controllers\Analytics\Actions\ReturnRateInsightForWindowAction;
use App\Http\Controllers\Analytics\Actions\PaymentMethodsForWindowAction;
use App\Http\Controllers\Analytics\Actions\OrderCountVsProfitForWindowAction;
use App\Http\Controllers\Analytics\Actions\OrdersTableForWindowAction;
use App\Http\Controllers\Analytics\Actions\TopCategoriesByProfitForWindowAction;
use App\Http\Controllers\Analytics\Actions\TopCategoriesByOrdersForWindowAction;
use App\Http\Controllers\Analytics\Actions\TopProductsBySalesForWindowAction;
use App\Http\Controllers\Analytics\Actions\TrendingProductsForWindowAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DashboardAnalyticsv2Controller extends Controller
{
    public function atAGlance(Request $request, ComposeAtAGlanceKpisAction $composeAtAGlanceKpis): JsonResponse
    {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        try {
            $result = $composeAtAGlanceKpis->execute($input);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(array_merge([
            'success' => true,
        ], $result));
    }

    public function courierBreakdown(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        CourierAnalyticsForWindowAction $courierAnalytics,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        try {
            $resolved = $resolveWindows->execute($input);
            $couriers = $courierAnalytics->execute($resolved['current']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'couriers' => $couriers,
        ]);
    }

    public function quotationFunnel(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        QuotationFunnelForWindowAction $quotationFunnel,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        try {
            $resolved = $resolveWindows->execute($input);
            $funnel = $quotationFunnel->execute($resolved['current']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'quotation_funnel' => $funnel,
        ]);
    }

    public function revenueTrend(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        RevenueTrendForWindowAction $revenueTrend,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        try {
            $resolved = $resolveWindows->execute($input);
            $currentWindow = $resolved['current'];
            $previousWindow = $resolved['previous'];

            $series = $revenueTrend->execute($currentWindow);

            $currentTotal = array_sum($series['revenue'] ?? []);
            $previousTotal = $revenueTrend->sumRevenue($previousWindow);

            $eps = 0.0001;
            $trend = 'flat';
            if ($currentTotal > $previousTotal + $eps) {
                $trend = 'up';
            } elseif ($currentTotal < $previousTotal - $eps) {
                $trend = 'down';
            }

            if (abs($previousTotal) < $eps) {
                $pct = $currentTotal > $eps ? 100.0 : 0.0;
            } else {
                $pct = (($currentTotal - $previousTotal) / $previousTotal) * 100;
            }
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'series' => $series,
            'compared' => [
                'trend' => $trend,
                'percentage' => round((float) $pct, 2),
                'current_total' => round((float) $currentTotal, 2),
                'previous_total' => round((float) $previousTotal, 2),
            ],
        ]);
    }

    public function revExpProfitTrend(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        RevExpProfitTrendForWindowAction $trend,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        try {
            $resolved = $resolveWindows->execute($input);
            $series = $trend->execute($resolved['current']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'series' => $series,
        ]);
    }

    public function topProductsBySales(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        TopProductsBySalesForWindowAction $topProducts,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        $limit = (int) $request->query('limit', 10);

        try {
            $resolved = $resolveWindows->execute($input);
            $items = $topProducts->execute($resolved['current'], $limit);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }

    public function paymentMethods(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        PaymentMethodsForWindowAction $paymentMethods,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        try {
            $resolved = $resolveWindows->execute($input);
            $data = $paymentMethods->execute($resolved['current']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
        ] + $data);
    }

    public function topCategoriesByOrders(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        TopCategoriesByOrdersForWindowAction $topCategories,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        $limit = (int) $request->query('limit', 5);

        try {
            $resolved = $resolveWindows->execute($input);
            $items = $topCategories->execute($resolved['current'], $limit);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }

    public function orderCountVsProfit(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        OrderCountVsProfitForWindowAction $action,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        try {
            $resolved = $resolveWindows->execute($input);
            $series = $action->execute($resolved['current']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'series' => $series,
        ]);
    }

    public function topCategoriesByProfit(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        TopCategoriesByProfitForWindowAction $action,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        $limit = (int) $request->query('limit', 10);

        try {
            $resolved = $resolveWindows->execute($input);
            $items = $action->execute($resolved['current'], $limit);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }

    public function ordersTable(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        OrdersTableForWindowAction $action,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        $mode = (string) $request->query('mode', 'high_value');
        if (! in_array($mode, ['pending', 'high_value'], true)) {
            $mode = 'high_value';
        }

        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 10);
        $minTotal = (float) $request->query('min_total', 0);

        try {
            $resolved = $resolveWindows->execute($input);
            $payload = $action->execute($resolved['current'], $mode, $page, $perPage, $minTotal);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['success' => true] + $payload);
    }

    public function trendingProducts(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        TrendingProductsForWindowAction $action,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 10);

        try {
            $resolved = $resolveWindows->execute($input);
            $payload = $action->execute($resolved['current'], $page, $perPage);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['success' => true] + $payload);
    }

    public function categoryProfitShare(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        CategoryProfitShareForWindowAction $action,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        $limit = (int) $request->query('limit', 10);

        try {
            $resolved = $resolveWindows->execute($input);
            $payload = $action->execute($resolved['current'], $limit);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['success' => true] + $payload);
    }

    public function expensesByCategory(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        ExpensesByCategoryForWindowAction $action,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        $limit = (int) $request->query('limit', 10);

        try {
            $resolved = $resolveWindows->execute($input);
            $payload = $action->execute($resolved['current'], $limit);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['success' => true] + $payload);
    }

    public function customerBreakdown(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        CustomerBreakdownForWindowAction $action,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        try {
            $resolved = $resolveWindows->execute($input);
            $payload = $action->execute($resolved['current']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['success' => true, 'breakdown' => $payload]);
    }

    public function websiteVisitors(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        WebsiteVisitorsForWindowAction $action,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        try {
            $resolved = $resolveWindows->execute($input);
            $current = $action->execute($resolved['current']);
            $previous = $action->execute($resolved['previous']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $trend = 'flat';
        $eps = 0.0001;
        if ($current > $previous + $eps) {
            $trend = 'up';
        } elseif ($current < $previous - $eps) {
            $trend = 'down';
        }

        if (abs($previous) < $eps) {
            $percentage = $current > $eps ? 100.0 : 0.0;
        } else {
            $percentage = round((($current - $previous) / $previous) * 100, 2);
        }

        return response()->json([
            'success' => true,
            'current' => round((float) $current, 2),
            'compared' => [
                'trend' => $trend,
                'value' => round((float) $previous, 2),
                'percentage' => $percentage,
            ],
        ]);
    }

    public function returnRateInsight(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        ReturnRateInsightForWindowAction $action,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        try {
            $resolved = $resolveWindows->execute($input);
            $payload = $action->execute($resolved['current']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['success' => true] + $payload);
    }

    public function monthlyProfitLoss(
        Request $request,
        ResolveAnalyticsWindowsAction $resolveWindows,
        MonthlyProfitLossForWindowAction $action,
    ): JsonResponse {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        try {
            $resolved = $resolveWindows->execute($input);
            $payload = $action->execute($resolved['current']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['success' => true] + $payload);
    }
}
