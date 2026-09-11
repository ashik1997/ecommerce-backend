<?php

namespace App\Http\Controllers\Courier\Settlement;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Controller;
use App\Models\ProductOrder;
use App\Models\ProductOrderCourierMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CourierSettlementController extends Controller
{
    public function __construct(
        protected CourierStatusNormalizer $normalizer,
        protected CourierSettlementAccountingService $accounting,
        protected CourierReturnInventoryService $inventory,
    ) {}

    public function index()
    {
        $couriers = ProductOrderCourierMethod::orderBy('title')->get(['id', 'title']);
        $accounts = AcAccount::where('status', 'active')
            ->whereIn('account_type', ['asset'])
            ->orderBy('sort_code')
            ->orderBy('account_name')
            ->get(['id', 'account_name', 'account_selection_name', 'balance']);

        return view('backend.courier_settlement.index', compact('couriers', 'accounts'));
    }

    public function orders(Request $request)
    {
        $request->validate([
            'courier' => 'required|string|max:50',
            'status' => 'nullable|string|max:50',
        ]);

        $courier = strtolower($request->courier);
        $query = ProductOrder::with(['customer:id,name,phone', 'order_products'])
            ->where('settled_from_courier', 0)
            ->where('is_couriered', 1)
            ->where('is_accounting_posted', 1)
            ->where('courier_info->courier', $courier)
            ->orderBy('shipping_date')
            ->orderBy('id');

        $orders = $query->get()->map(function (ProductOrder $order) {
            $normalized = $this->normalizer->normalize($order);
            return $this->orderPayload($order, $normalized);
        });

        if ($request->filled('status')) {
            $orders = $orders->where('normalized_status', $request->status)->values();
        }

        return response()->json([
            'success' => true,
            'data' => $orders->values(),
            'summary' => [
                'orders' => $orders->count(),
                'receivable' => round($orders->sum('cod_amount'), 2),
                'courier_cost' => round($orders->sum('delivery_cost'), 2),
                'suggested_receive' => round($orders->sum('suggested_received_amount'), 2),
            ],
        ]);
    }

    public function sync(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'integer|exists:product_orders,id',
        ]);

        $orders = ProductOrder::whereIn('id', $request->order_ids)->get();
        $updated = [];

        foreach ($orders as $order) {
            $raw = $this->fetchCourierStatus($order);
            $normalized = $this->normalizer->normalize($order, $raw);
            $this->applyCourierStatusToOrder($order, $normalized);
            $updated[] = $this->orderPayload($order->fresh(['customer:id,name,phone', 'order_products']), $normalized);
        }

        return response()->json(['success' => true, 'data' => $updated]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'courier' => 'required|string|max:50',
            'account_id' => 'required|exists:ac_accounts,id',
            'settlement_amount' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'orders' => 'required|array|min:1',
            'orders.*.order_id' => 'required|integer|exists:product_orders,id',
            'orders.*.normalized_status' => 'required|string|max:50',
            'orders.*.received_amount' => 'nullable|numeric|min:0',
            'orders.*.courier_cost' => 'nullable|numeric|min:0',
            'orders.*.returned_qty' => 'nullable|numeric|min:0',
        ]);

        $settlement = DB::transaction(function () use ($request) {
            $courier = strtolower($request->courier);
            $settlement = CourierSettlement::create([
                'settlement_code' => $this->nextSettlementCode($courier),
                'courier' => $courier,
                'settlement_date' => Carbon::now()->toDateString(),
                'account_id' => $request->account_id,
                'settlement_amount' => (float) $request->settlement_amount,
                'note' => $request->note,
                'creator' => Auth::id(),
                'status' => 'posted',
            ]);

            $totals = [
                'receivable' => 0,
                'expense' => 0,
                'received' => 0,
                'orders' => 0,
            ];

            foreach ($request->orders as $row) {
                $order = ProductOrder::with('order_products')->lockForUpdate()->findOrFail($row['order_id']);
                if ((int) ($order->settled_from_courier ?? 0) === 1) {
                    continue;
                }

                $normalized = $this->normalizer->normalize($order);
                $status = $row['normalized_status'] ?: $normalized['normalized_status'];
                $codAmount = (float) ($normalized['cod_amount'] ?: $order->total);
                $courierCost = (float) ($row['courier_cost'] ?? $normalized['delivery_cost'] ?? 0);
                $receivedAmount = (float) ($row['received_amount'] ?? 0);
                $returnedQty = (float) ($row['returned_qty'] ?? 0);
                $receivableReduction = min($codAmount, $receivedAmount + $courierCost);
                $returnRatio = 0;
                $restockedQty = 0;
                $inventoryAdjusted = false;

                if (in_array($status, ['full_return', 'cancel_not_received'], true)) {
                    $restockedQty = $this->inventory->restock($order);
                    $returnRatio = 1;
                    $inventoryAdjusted = true;
                    $receivableReduction = $courierCost;
                    $receivedAmount = 0;
                } elseif ($status === 'partial_return' && $returnedQty > 0) {
                    $totalQty = max(1, (float) $order->order_products->sum('qty'));
                    $restockedQty = $this->inventory->restock($order, min($returnedQty, $totalQty));
                    $returnRatio = min(1, $restockedQty / $totalQty);
                    $inventoryAdjusted = $restockedQty > 0;
                    $receivableReduction = min($codAmount, $receivedAmount + $courierCost);
                }

                $note = "Courier settlement {$settlement->settlement_code} for order {$order->order_code}";
                $this->accounting->postMoneySettlement($order, (int) $request->account_id, $receivedAmount, $courierCost, $receivableReduction, $note);
                $this->accounting->postReturnAdjustments($order, $returnRatio, $note);

                CourierSettlementOrder::create([
                    'courier_settlement_id' => $settlement->id,
                    'product_order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'courier' => $courier,
                    'tracking_id' => $normalized['tracking_id'],
                    'raw_status' => $normalized['raw_status'],
                    'normalized_status' => $status,
                    'cod_amount' => $codAmount,
                    'courier_cost' => $courierCost,
                    'received_amount' => $receivedAmount,
                    'returned_amount' => round($codAmount - $receivedAmount, 2),
                    'restocked_qty' => $restockedQty,
                    'is_inventory_adjusted' => $inventoryAdjusted,
                    'is_accounting_posted' => true,
                    'raw_response' => $normalized['raw_response'],
                    'notes' => [
                        'return_ratio' => $returnRatio,
                        'operator_note' => $request->note,
                    ],
                ]);

                $this->markOrderSettled($order, $settlement, $status, $normalized);

                $totals['receivable'] += $codAmount;
                $totals['expense'] += $courierCost;
                $totals['received'] += $receivedAmount;
                $totals['orders']++;
            }

            $settlement->update([
                'receivable_total' => $totals['receivable'],
                'courier_expense_total' => $totals['expense'],
                'received_total' => $totals['received'],
                'total_orders' => $totals['orders'],
            ]);

            return $settlement;
        });

        return response()->json([
            'success' => true,
            'message' => 'Courier settlement posted successfully.',
            'redirect' => $request->routeIs('delivery-management.*')
                ? route('delivery-management.courier-settlements.show', $settlement->id)
                : route('courier-settlements.show', $settlement->id),
        ]);
    }

    public function show(CourierSettlement $settlement)
    {
        $settlement->load(['orders.order.customer', 'account']);
        return view('backend.courier_settlement.show', compact('settlement'));
    }

    public function print(CourierSettlement $settlement)
    {
        $settlement->load(['orders.order.customer', 'account']);
        return view('backend.courier_settlement.print', compact('settlement'));
    }

    protected function orderPayload(ProductOrder $order, array $normalized): array
    {
        return [
            'id' => $order->id,
            'order_code' => $order->order_code,
            'customer_name' => $order->customer_name ?: optional($order->customer)->name,
            'customer_phone' => $order->customer_phone ?: optional($order->customer)->phone,
            'shipping_date' => optional($order->shipping_date)->format('Y-m-d H:i:s') ?: (string) $order->shipping_date,
            'total' => (float) $order->total,
            'delivery_fee' => (float) $order->delivery_fee,
            'tracking_id' => $normalized['tracking_id'],
            'raw_status' => $normalized['raw_status'],
            'normalized_status' => $normalized['normalized_status'],
            'cod_amount' => $normalized['cod_amount'],
            'delivery_cost' => $normalized['delivery_cost'],
            'suggested_received_amount' => $normalized['suggested_received_amount'],
            'total_qty' => (float) $order->order_products->sum('qty'),
        ];
    }

    protected function fetchCourierStatus(ProductOrder $order): array
    {
        $info = is_array($order->courier_info) ? $order->courier_info : [];
        if (!empty($info['order_status_url'])) {
            try {
                $response = Http::timeout(20)->get($info['order_status_url']);
                if ($response->successful()) {
                    return $response->json() ?: [];
                }
            } catch (\Throwable $e) {
                return ['sync_error' => $e->getMessage()];
            }
        }

        return $info;
    }

    protected function applyCourierStatusToOrder(ProductOrder $order, array $normalized): void
    {
        $info = is_array($order->courier_info) ? $order->courier_info : [];
        $order->courier_info = array_merge($info, [
            'status' => $normalized['raw_status'],
            'normalized_status' => $normalized['normalized_status'],
            'collected_amount' => $normalized['collected_amount'],
            'delivery_fee' => $normalized['delivery_cost'],
            'last_status_synced_at' => Carbon::now()->toDateTimeString(),
        ]);
        $order->delivery_status = $normalized['normalized_status'];
        $tracks = is_array($order->order_tracks) ? $order->order_tracks : [];
        $tracks[] = [
            'event' => 'courier_status_synced',
            'status' => $normalized['raw_status'],
            'normalized_status' => $normalized['normalized_status'],
            'tracking_id' => $normalized['tracking_id'],
            'at' => Carbon::now()->toDateTimeString(),
            'user_id' => Auth::id(),
        ];
        $order->order_tracks = $tracks;
        $order->save();
    }

    protected function markOrderSettled(ProductOrder $order, CourierSettlement $settlement, string $status, array $normalized): void
    {
        $tracks = is_array($order->order_tracks) ? $order->order_tracks : [];
        $tracks[] = [
            'event' => 'courier_settled',
            'settlement_id' => $settlement->id,
            'settlement_code' => $settlement->settlement_code,
            'normalized_status' => $status,
            'at' => Carbon::now()->toDateTimeString(),
            'user_id' => Auth::id(),
        ];

        $order->settled_from_courier = 1;
        $order->courier_settlement_date = Carbon::now()->toDateString();
        $order->delivery_status = $status;
        $order->order_tracks = $tracks;
        $order->save();
    }

    protected function nextSettlementCode(string $courier): string
    {
        return 'CS-' . strtoupper(Str::slug($courier, '')) . '-' . now()->format('ymdHis');
    }
}
