<?php

namespace App\Http\Controllers\Analytics\Actions;

use App\Models\ProductOrder;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Paginated orders list for the dashboard table.
 *
 * Modes:
 * - pending: all pending orders
 * - high_value: pending orders ordered by total desc, optionally filtered by min_total
 *
 * Date filter uses product_orders.created_at between window start/end (pending orders may not have sale_date).
 */
class OrdersTableForWindowAction
{
    /**
     * @param  array{start: Carbon, end: Carbon}  $window
     * @return array{
     *   counts: array{pending: int, high_value: int},
     *   data: array<int, array<string, mixed>>,
     *   pagination: array{current_page:int, last_page:int, per_page:int, total:int}
     * }
     */
    public function execute(array $window, string $mode, int $page = 1, int $perPage = 10, float $minTotal = 0): array
    {
        $page = max(1, (int) $page);
        $perPage = max(1, min(50, (int) $perPage));

        $start = $window['start']->copy()->startOfDay();
        $end = $window['end']->copy()->endOfDay();

        $base = ProductOrder::query()
            ->leftJoin('customers as cu', 'cu.id', '=', 'product_orders.customer_id')
            ->where('product_orders.status', 'active')
            ->whereBetween('product_orders.created_at', [$start, $end])
            ->select([
                'product_orders.id',
                'product_orders.order_code',
                'product_orders.customer_name',
                'cu.name as customer_name2',
                'product_orders.order_status',
                'product_orders.total',
                'product_orders.created_at',
            ]);

        $pendingQuery = (clone $base)->where('product_orders.order_status', 'pending');

        $highValueQuery = (clone $pendingQuery);
        if ($minTotal > 0) {
            $highValueQuery->where('product_orders.total', '>=', $minTotal);
        }

        $counts = [
            'pending' => (int) (clone $pendingQuery)->count(),
            'high_value' => (int) (clone $highValueQuery)->count(),
        ];

        $listQuery = $mode === 'pending' ? $pendingQuery : $highValueQuery;
        $listQuery->orderByDesc('product_orders.total')->orderByDesc('product_orders.id');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $listQuery->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(function ($row) {
            $customer = $row->customer_name2 ?? $row->customer_name ?? '—';
            return [
                'id' => (int) $row->id,
                'order_code' => (string) $row->order_code,
                'customer_name' => (string) $customer,
                'order_status' => (string) ($row->order_status ?? ''),
                'total' => (float) ($row->total ?? 0),
                'created_at' => $row->created_at ? Carbon::parse($row->created_at)->toIso8601String() : null,
            ];
        })->values()->toArray();

        return [
            'counts' => $counts,
            'data' => $data,
            'pagination' => [
                'current_page' => (int) $paginator->currentPage(),
                'last_page' => (int) $paginator->lastPage(),
                'per_page' => (int) $paginator->perPage(),
                'total' => (int) $paginator->total(),
            ],
        ];
    }
}

