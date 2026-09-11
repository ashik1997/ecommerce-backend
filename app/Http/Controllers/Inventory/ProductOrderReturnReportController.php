<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ManualProductReturn;
use App\Models\ManualProductReturnItem;
use App\Models\ProductOrderRefund;
use App\Models\ProductOrderReturn;
use App\Models\ProductOrderReturnProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DataTables;

class ProductOrderReturnReportController extends Controller
{
    public function dashboard(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        $returns = ProductOrderReturn::with(['customer', 'originalOrder'])
            ->where('status', 'active')
            ->whereBetween('return_date', [$from, $to]);

        $manualReturns = ManualProductReturn::with('customer')
            ->where('status', 'active')
            ->whereBetween('return_date', [$from, $to]);

        $refunds = ProductOrderRefund::with(['return', 'order', 'customer', 'paymentType'])
            ->where('status', 'active')
            ->whereBetween('refund_date', [$from, $to]);

        $returnIds = (clone $returns)->pluck('id');
        $manualReturnIds = (clone $manualReturns)->pluck('id');
        $orderReturnedValue = (float) (clone $returns)->sum('total');
        $manualReturnedValue = (float) (clone $manualReturns)->sum('total');
        $returnedValue = $orderReturnedValue + $manualReturnedValue;
        $refundedAgainstFilteredReturns = $returnIds->isEmpty()
            ? 0
            : (float) ProductOrderRefund::where('status', 'active')
                ->whereIn('product_order_return_id', $returnIds)
                ->sum('refund_amount');

        $summary = [
            'return_count' => (clone $returns)->count() + (clone $manualReturns)->count(),
            'order_return_count' => (clone $returns)->count(),
            'manual_return_count' => (clone $manualReturns)->count(),
            'returned_qty' => ($returnIds->isEmpty()
                ? 0
                : (int) ProductOrderReturnProduct::whereIn('product_order_return_id', $returnIds)->sum('qty'))
                + ($manualReturnIds->isEmpty()
                    ? 0
                    : (int) ManualProductReturnItem::whereIn('manual_product_return_id', $manualReturnIds)->sum('qty')),
            'returned_value' => $returnedValue,
            'order_returned_value' => $orderReturnedValue,
            'manual_returned_value' => $manualReturnedValue,
            'refund_paid_in_range' => (float) (clone $refunds)->sum('refund_amount'),
            'refunded_against_returns' => $refundedAgainstFilteredReturns,
            'payable_for_returns' => max(0, $returnedValue - $refundedAgainstFilteredReturns),
            'pending_refund_count' => (clone $returns)
                ->whereRaw('COALESCE(total, 0) > COALESCE(refunded_amount, 0)')
                ->count() + (clone $manualReturns)->count(),
        ];

        $orderTopProducts = ProductOrderReturnProduct::query()
            ->select([
                'product_order_return_products.product_id',
                DB::raw('MAX(product_order_return_products.product_name) as product_name'),
                DB::raw('SUM(product_order_return_products.qty) as returned_qty'),
                DB::raw('SUM(product_order_return_products.total_price) as returned_value'),
            ])
            ->join('product_order_returns', 'product_order_returns.id', '=', 'product_order_return_products.product_order_return_id')
            ->where('product_order_returns.status', 'active')
            ->whereBetween('product_order_returns.return_date', [$from, $to])
            ->groupBy('product_order_return_products.product_id')
            ->get();

        $manualTopProducts = ManualProductReturnItem::query()
            ->select([
                'manual_product_return_items.product_id',
                DB::raw('MAX(manual_product_return_items.product_name) as product_name'),
                DB::raw('SUM(manual_product_return_items.qty) as returned_qty'),
                DB::raw('SUM(manual_product_return_items.total_price) as returned_value'),
            ])
            ->join('manual_product_returns', 'manual_product_returns.id', '=', 'manual_product_return_items.manual_product_return_id')
            ->where('manual_product_returns.status', 'active')
            ->whereBetween('manual_product_returns.return_date', [$from, $to])
            ->groupBy('manual_product_return_items.product_id')
            ->get();

        $topProducts = $orderTopProducts->concat($manualTopProducts)
            ->groupBy(fn($row) => $row->product_id ?: $row->product_name)
            ->map(function ($rows) {
                return (object) [
                    'product_id' => $rows->first()->product_id,
                    'product_name' => $rows->first()->product_name,
                    'returned_qty' => $rows->sum('returned_qty'),
                    'returned_value' => $rows->sum('returned_value'),
                ];
            })
            ->sortByDesc('returned_value')
            ->take(10)
            ->values();

        $paymentBreakdown = ProductOrderRefund::query()
            ->select([
                DB::raw('COALESCE(payment_type_snapshot, "Unknown") as payment_type'),
                DB::raw('COUNT(*) as refund_count'),
                DB::raw('SUM(refund_amount) as refund_amount'),
            ])
            ->where('status', 'active')
            ->whereBetween('refund_date', [$from, $to])
            ->groupBy(DB::raw('COALESCE(payment_type_snapshot, "Unknown")'))
            ->orderByDesc('refund_amount')
            ->get();

        $pendingOrderReturns = (clone $returns)
            ->whereRaw('COALESCE(total, 0) > COALESCE(refunded_amount, 0)')
            ->orderByDesc('return_date')
            ->limit(15)
            ->get()
            ->map(function ($return) {
                return (object) [
                    'source' => 'Order',
                    'code' => $return->return_code,
                    'order_code' => optional($return->originalOrder)->order_code ?? 'N/A',
                    'customer_name' => optional($return->customer)->name ?? 'N/A',
                    'date' => $return->return_date,
                    'payable' => max(0, (float) ($return->total ?? 0) - (float) ($return->refunded_amount ?? 0)),
                    'url' => route('ShowProductOrderReturn', $return->slug),
                ];
            });

        $pendingManualReturns = (clone $manualReturns)
            ->orderByDesc('return_date')
            ->limit(15)
            ->get()
            ->map(function ($return) {
                return (object) [
                    'source' => 'Manual',
                    'code' => $return->return_code,
                    'order_code' => 'Manual',
                    'customer_name' => optional($return->customer)->name ?? 'N/A',
                    'date' => $return->return_date,
                    'payable' => (float) ($return->total ?? 0),
                    'url' => route('ShowManualProductReturn', $return->slug),
                ];
            });

        $pendingReturns = $pendingOrderReturns->concat($pendingManualReturns)
            ->sortByDesc('date')
            ->take(15)
            ->values();

        $recentRefunds = (clone $refunds)
            ->orderByDesc('refund_date')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return view('backend.product_order_return.dashboard', compact(
            'from',
            'to',
            'summary',
            'topProducts',
            'paymentBreakdown',
            'pendingReturns',
            'recentRefunds'
        ));
    }

    public function refunds(Request $request)
    {
        if ($request->ajax()) {
            $data = ProductOrderRefund::with(['return', 'order', 'customer', 'paymentType'])
                ->orderByDesc('id');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('refund_date', fn($row) => $row->refund_date ? date('Y-m-d', strtotime($row->refund_date)) : '-')
                ->addColumn('return_code', fn($row) => optional($row->return)->return_code ?? 'N/A')
                ->addColumn('order_code', fn($row) => optional($row->order)->order_code ?? 'N/A')
                ->addColumn('customer_name', fn($row) => optional($row->customer)->name ?? 'N/A')
                ->addColumn('payment_type', fn($row) => $row->payment_type_snapshot ?? optional($row->paymentType)->payment_type ?? 'N/A')
                ->editColumn('refund_status', function ($row) {
                    $class = $row->status === 'active' ? 'success' : 'secondary';
                    $label = $row->status === 'active' ? ucfirst($row->refund_status) : 'Reversed';
                    return '<span class="badge badge-' . $class . '">' . $label . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<a class="btn btn-sm btn-info" target="_blank" href="' . route('ShowProductOrderRefund', $row->slug) . '"><i class="fas fa-eye"></i></a> ';
                    $btn .= '<a class="btn btn-sm btn-primary" target="_blank" href="' . route('PrintProductOrderRefund', $row->slug) . '"><i class="fas fa-print"></i></a>';
                    return $btn;
                })
                ->rawColumns(['refund_status', 'action'])
                ->make(true);
        }

        return view('backend.product_order_return.refunds');
    }

    private function dateRange(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->from)->toDateString()
            : now()->startOfMonth()->toDateString();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->toDateString()
            : now()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
