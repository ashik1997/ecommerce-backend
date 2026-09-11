<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Analytics\Actions\ResolveAnalyticsWindowsAction;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EcommerceAnalyticsController extends Controller
{
    private const FINANCE_STATUSES = ['accepted', 'processing', 'invoiced', 'delivered'];
    private const EXCLUDED_STATUSES = ['pending', 'canceled', 'cancelled', 'returned'];

    public function overview(Request $request, ResolveAnalyticsWindowsAction $resolveWindows): JsonResponse
    {
        $input = [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        try {
            $resolved = $resolveWindows->execute($input);
            $payload = $this->buildPayload($resolved['current']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
        ] + $payload);
    }

    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     */
    private function buildPayload(array $window): array
    {
        $start = $window['start']->toDateString();
        $end = $window['end']->toDateString();
        $dateSql = 'COALESCE(product_orders.sale_date, DATE(product_orders.created_at))';
        $financeSql = $this->inListSql('product_orders.order_status', self::FINANCE_STATUSES);
        $excludedSql = $this->inListSql('product_orders.order_status', self::EXCLUDED_STATUSES);
        $codExpr = $this->courierAmountExpression([
            'cod_amount',
            'collectable_amount',
            'amount_to_collect',
        ], 'product_orders.total');
        $courierCostExpr = $this->courierAmountExpression([
            'delivery_fee',
            'courier_fee',
            'cod_fee',
            'charge',
        ], 'product_orders.delivery_fee');

        $base = DB::table('product_orders')
            ->where('product_orders.status', 'active')
            ->where('product_orders.order_source', 'ecommerce')
            ->whereRaw("{$dateSql} >= ?", [$start])
            ->whereRaw("{$dateSql} <= ?", [$end]);

        $summary = (clone $base)
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw("SUM(CASE WHEN {$financeSql} THEN 1 ELSE 0 END) as accepted_orders")
            ->selectRaw("SUM(CASE WHEN {$excludedSql} THEN 1 ELSE 0 END) as excluded_orders")
            ->selectRaw("SUM(CASE WHEN product_orders.order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders")
            ->selectRaw("SUM(CASE WHEN product_orders.order_status IN ('canceled','cancelled') THEN 1 ELSE 0 END) as canceled_orders")
            ->selectRaw("SUM(CASE WHEN {$financeSql} THEN COALESCE(product_orders.total, 0) ELSE 0 END) as accepted_value")
            ->selectRaw("SUM(CASE WHEN {$excludedSql} THEN COALESCE(product_orders.total, 0) ELSE 0 END) as excluded_value")
            ->selectRaw("SUM(CASE WHEN {$financeSql} AND product_orders.is_couriered = 1 THEN 1 ELSE 0 END) as couriered_orders")
            ->selectRaw("SUM(CASE WHEN {$financeSql} AND product_orders.settled_from_courier = 1 THEN 1 ELSE 0 END) as settled_orders")
            ->selectRaw("SUM(CASE WHEN {$financeSql} THEN {$codExpr} ELSE 0 END) as cod_total")
            ->selectRaw("SUM(CASE WHEN {$financeSql} THEN {$courierCostExpr} ELSE 0 END) as courier_cost_total")
            ->first();
        $settlementPending = $this->courierReceivableBalance($start, $end);

        $totalOrders = (int) ($summary->total_orders ?? 0);
        $acceptedOrders = (int) ($summary->accepted_orders ?? 0);
        $excludedOrders = (int) ($summary->excluded_orders ?? 0);
        $courieredOrders = (int) ($summary->couriered_orders ?? 0);
        $settledOrders = (int) ($summary->settled_orders ?? 0);

        $statusRows = (clone $base)
            ->select('product_orders.order_status', DB::raw('COUNT(*) as count'), DB::raw('COALESCE(SUM(product_orders.total), 0) as value'))
            ->groupBy('product_orders.order_status')
            ->get()
            ->keyBy('order_status');

        $statusCards = collect([
            ['key' => 'pending', 'label' => 'Pending Review', 'icon' => 'fa-clock'],
            ['key' => 'accepted', 'label' => 'Accepted', 'icon' => 'fa-circle-check'],
            ['key' => 'processing', 'label' => 'Processing', 'icon' => 'fa-gears'],
            ['key' => 'invoiced', 'label' => 'Invoiced', 'icon' => 'fa-file-invoice'],
            ['key' => 'delivered', 'label' => 'Delivered', 'icon' => 'fa-truck'],
            ['key' => 'canceled', 'label' => 'Canceled', 'icon' => 'fa-ban'],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'icon' => 'fa-ban'],
            ['key' => 'returned', 'label' => 'Returned', 'icon' => 'fa-rotate-left'],
        ])->map(function (array $item) use ($statusRows, $totalOrders) {
            $row = $statusRows->get($item['key']);
            $count = (int) ($row->count ?? 0);

            return [
                'key' => $item['key'],
                'label' => $item['label'],
                'icon' => $item['icon'],
                'count' => $count,
                'value' => round((float) ($row->value ?? 0), 2),
                'pct' => $totalOrders > 0 ? round(($count / $totalOrders) * 100, 1) : 0,
                'finance_eligible' => in_array($item['key'], self::FINANCE_STATUSES, true),
            ];
        })->values()->toArray();

        $recentOrders = (clone $base)
            ->leftJoin('customers as cu', 'cu.id', '=', 'product_orders.customer_id')
            ->select([
                'product_orders.id',
                'product_orders.order_code',
                'product_orders.customer_name',
                'product_orders.customer_phone',
                'product_orders.order_status',
                'product_orders.total',
                'product_orders.is_couriered',
                'product_orders.settled_from_courier',
                'product_orders.created_at',
                'cu.name as customer_name2',
            ])
            ->selectRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(product_orders.courier_info, '$.courier'))) as courier")
            ->orderByDesc('product_orders.id')
            ->limit(6)
            ->get()
            ->map(function ($row) {
                $status = (string) ($row->order_status ?? '');
                return [
                    'id' => (int) $row->id,
                    'order_code' => (string) $row->order_code,
                    'customer_name' => (string) ($row->customer_name2 ?: $row->customer_name ?: 'Guest'),
                    'customer_phone' => (string) ($row->customer_phone ?? ''),
                    'order_status' => $status,
                    'total' => (float) ($row->total ?? 0),
                    'is_couriered' => (int) ($row->is_couriered ?? 0),
                    'settled_from_courier' => (int) ($row->settled_from_courier ?? 0),
                    'courier' => $row->courier ?: null,
                    'finance_eligible' => in_array($status, self::FINANCE_STATUSES, true),
                    'created_at' => $row->created_at ? Carbon::parse($row->created_at)->toIso8601String() : null,
                ];
            })
            ->values()
            ->toArray();

        return [
            'summary' => [
                'total_orders' => $totalOrders,
                'accepted_orders' => $acceptedOrders,
                'excluded_orders' => $excludedOrders,
                'pending_orders' => (int) ($summary->pending_orders ?? 0),
                'canceled_orders' => (int) ($summary->canceled_orders ?? 0),
                'couriered_orders' => $courieredOrders,
                'settled_orders' => $settledOrders,
                'accepted_value' => round((float) ($summary->accepted_value ?? 0), 2),
                'excluded_value' => round((float) ($summary->excluded_value ?? 0), 2),
                'cod_total' => round((float) ($summary->cod_total ?? 0), 2),
                'courier_cost_total' => round((float) ($summary->courier_cost_total ?? 0), 2),
                'settlement_pending' => round($settlementPending, 2),
                'acceptance_rate' => $totalOrders > 0 ? round(($acceptedOrders / $totalOrders) * 100, 1) : 0,
                'excluded_rate' => $totalOrders > 0 ? round(($excludedOrders / $totalOrders) * 100, 1) : 0,
                'settlement_rate' => $courieredOrders > 0 ? round(($settledOrders / $courieredOrders) * 100, 1) : 0,
            ],
            'statuses' => $statusCards,
            'recent_orders' => $recentOrders,
        ];
    }

    private function inListSql(string $column, array $values): string
    {
        $quoted = collect($values)
            ->map(fn ($value) => DB::getPdo()->quote($value))
            ->implode(',');

        return "{$column} IN ({$quoted})";
    }

    private function courierAmountExpression(array $jsonKeys, string $fallbackColumn): string
    {
        $parts = collect($jsonKeys)->map(function (string $key) {
            return "CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(product_orders.courier_info, '$.{$key}')), '') AS DECIMAL(15,2))";
        })->push("COALESCE({$fallbackColumn}, 0)");

        return 'COALESCE(' . $parts->implode(', ') . ', 0)';
    }

    private function courierReceivableBalance(string $start, string $end): float
    {
        $courierReceivableAccountId = DB::table('ac_accounts')
            ->where('account_selection_name', 'courier_receivable')
            ->where('status', 'active')
            ->value('id');

        if (!$courierReceivableAccountId) {
            return 0;
        }

        $financeSql = $this->inListSql('product_orders.order_status', self::FINANCE_STATUSES);
        $dateSql = 'COALESCE(product_orders.sale_date, DATE(product_orders.created_at))';

        return (float) DB::table('ac_transactions')
            ->join('product_orders', 'product_orders.id', '=', 'ac_transactions.ref_sales_id')
            ->where('ac_transactions.status', 'active')
            ->where('product_orders.status', 'active')
            ->where('product_orders.order_source', 'ecommerce')
            ->where('product_orders.is_accounting_posted', 1)
            ->whereRaw($financeSql)
            ->whereRaw("{$dateSql} >= ?", [$start])
            ->whereRaw("{$dateSql} <= ?", [$end])
            ->where(function ($query) use ($courierReceivableAccountId) {
                $query->where('ac_transactions.debit_account_id', $courierReceivableAccountId)
                    ->orWhere('ac_transactions.credit_account_id', $courierReceivableAccountId);
            })
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN ac_transactions.debit_account_id = ? THEN COALESCE(ac_transactions.debit_amt, 0) ELSE 0 END), 0) -
                 COALESCE(SUM(CASE WHEN ac_transactions.credit_account_id = ? THEN COALESCE(ac_transactions.credit_amt, 0) ELSE 0 END), 0) as balance',
                [$courierReceivableAccountId, $courierReceivableAccountId]
            )
            ->value('balance');
    }
}
