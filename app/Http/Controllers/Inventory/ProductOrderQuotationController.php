<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Inventory\Models\ProductPurchaseOtherCharge;
use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use App\Models\ProductOrderQuotation;
use App\Models\ProductOrderQuotationProduct;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductOrderQuotationController extends Controller
{
    // ─── Helpers ────────────────────────────────────────────────────────────

    public function latestCode()
    {
        $prefix = Carbon::now()->format('ym'); // e.g. "2502"

        $latest = ProductOrderQuotation::where('order_code', 'like', "QT".$prefix . '%')
            ->orderBy('order_code', 'desc')
            ->value('order_code');

        $next = $latest ? str_pad((int) substr($latest, -4) + 1, 4, '0', STR_PAD_LEFT) : '0001';

        $code = 'QT' . $prefix . $next;
        
        if(request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $code,
            ]);
        }

        return $code;
    }

    private function calcOtherCharges(?array $charges, float $subtotal): float
    {
        if (empty($charges)) {
            return 0;
        }
        $total = 0;
        foreach ($charges as $c) {
            if (!is_array($c) || !isset($c['amount'])) {
                continue;
            }
            $amount = (float) $c['amount'];
            if (isset($c['type']) && $c['type'] === 'percent') {
                $total += ($subtotal * $amount) / 100;
            } else {
                $total += $amount;
            }
        }
        return $total;
    }

    // ─── Index ───────────────────────────────────────────────────────────────

    public function index()
    {
        $productWarehouses = ProductWarehouse::where('status', 'active')->get();
        return view('backend.product_order_quotations.view', compact('productWarehouses'));
    }

    /**
     * JSON list endpoint consumed by the Vue list page.
     */
    public function list(Request $request)
    {
        $q = ProductOrderQuotation::with(['customer', 'warehouse', 'creator'])
            ->orderBy('id', 'desc');

        if ($search = $request->search) {
            $q->where(function ($query) use ($search) {
                $query->where('order_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->order_status) {
            $q->where('order_status', $status);
        }

        if ($warehouseId = $request->warehouse_id) {
            $q->where('product_warehouse_id', $warehouseId);
        }

        if ($from = $request->date_from) {
            $q->whereDate('sale_date', '>=', $from);
        }

        if ($to = $request->date_to) {
            $q->whereDate('sale_date', '<=', $to);
        }

        $perPage = (int) ($request->per_page ?? 20);
        $paginated = $q->paginate($perPage);

        $paginated->getCollection()->transform(function ($row) {
            return [
                'id'               => $row->id,
                'slug'             => $row->slug,
                'order_code'       => $row->order_code,
                'customer_name'    => $row->customer_name ?? ($row->customer?->name ?? '—'),
                'customer_phone'   => $row->customer_phone ?? ($row->customer?->phone ?? '—'),
                'sale_date'        => $row->sale_date,
                'due_date'         => $row->due_date,
                'order_status'     => $row->order_status,
                'total'            => $row->total,
                'paid_amount'      => $row->paid_amount,
                'due_amount'       => $row->due_amount,
                'warehouse_name'   => $row->warehouse?->title ?? '—',
                'created_at'       => $row->created_at?->format('Y-m-d'),
            ];
        });

        $analytics = [
            'all'       => ['count' => ProductOrderQuotation::count(), 'total' => ProductOrderQuotation::sum('total')],
            'pending'   => ['count' => ProductOrderQuotation::where('order_status', 'pending')->count(),   'total' => ProductOrderQuotation::where('order_status', 'pending')->sum('total')],
            'in_review' => ['count' => ProductOrderQuotation::where('order_status', 'in_review')->count(), 'total' => ProductOrderQuotation::where('order_status', 'in_review')->sum('total')],
            'invoiced'  => ['count' => ProductOrderQuotation::where('order_status', 'invoiced')->count(),  'total' => ProductOrderQuotation::where('order_status', 'invoiced')->sum('total')],
            'canceled'  => ['count' => ProductOrderQuotation::where('order_status', 'canceled')->count(),  'total' => ProductOrderQuotation::where('order_status', 'canceled')->sum('total')],
        ];

        return response()->json([
            'data'      => $paginated,
            'analytics' => $analytics,
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function create()
    {
        $productWarehouses   = ProductWarehouse::where('status', 'active')->get();
        $other_charges_types = ProductPurchaseOtherCharge::where('status', 'active')->get();
        $quote_code          = $this->latestCode();

        return view('backend.product_order_quotations.form', compact(
            'productWarehouses',
            'other_charges_types',
            'quote_code'
        ));
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $input = $request->isJson() ? $request->json()->all() : $request->all();

        $validator = Validator::make($input, [
            'order_code'           => ['required', 'unique:product_order_quotations,order_code'],
            'customer_id'          => ['required'],
            'product_warehouse_id' => ['required'],
            'sale_date'            => ['required'],
            'cart'                 => ['required', 'array', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            [$fields] = $this->buildQuotationData($input);

            $quotation = ProductOrderQuotation::create(array_merge($fields, [
                'order_code'   => $input['order_code'],
                'order_source' => 'manual',
                'user_id'      => Auth::id(),
                'creator'      => Auth::id(),
                'slug'         => Str::uuid(),
                'status'       => 'active',
                'request_data' => ($input),
            ]));

            $this->saveCartItems($quotation, $input);

            DB::commit();
            return response()->json([
                'success'  => true,
                'message'  => 'Quotation created.',
                // 'redirect' => route('ViewAllProductOrderQuotations'),
                'redirect' => url(route('order.invoice', ['slug' => $quotation->slug, 'type' => 'quotation'])),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─── Edit ─────────────────────────────────────────────────────────────────

    public function edit(string $slug)
    {
        $data = ProductOrderQuotation::with('order_products.product')->where('slug', $slug)->firstOrFail();

        $productWarehouses   = ProductWarehouse::where('status', 'active')->get();
        $other_charges_types = ProductPurchaseOtherCharge::where('status', 'active')->get();
        $quote_code          = $data->order_code;

        return view('backend.product_order_quotations.form', compact(
            'data',
            'productWarehouses',
            'other_charges_types',
            'quote_code'
        ));
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, string $slug)
    {
        $quotation = ProductOrderQuotation::where('slug', $slug)->firstOrFail();

        $input = $request->isJson() ? $request->json()->all() : $request->all();

        $validator = Validator::make($input, [
            'customer_id'          => ['required'],
            'product_warehouse_id' => ['required'],
            'sale_date'            => ['required'],
            'cart'                 => ['required', 'array', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            [$fields] = $this->buildQuotationData($input);

            $quotation->update(array_merge($fields, [
                'order_status' => $input['order_status'] ?? $quotation->order_status,
            ]));

            $quotation->order_products()->delete();
            $this->saveCartItems($quotation, $input);

            DB::commit();
            return response()->json([
                'success'  => true,
                'message'  => 'Quotation updated.',
                // 'redirect' => route('ViewAllProductOrderQuotations'),
                'redirect' => url(route('order.invoice', ['slug' => $quotation->slug, 'type' => 'quotation'])),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ─── Shared builder helpers ───────────────────────────────────────────────

    /**
     * Build the common field array and derived totals from the JSON payload.
     * Returns [$fields, $grandTotal, $otherChargesJson, $otherChargeAmt, $paymentsJson, $totals].
     */
    private function buildQuotationData(array $input): array
    {
        $totals       = $input['totals'] ?? [];
        $subtotal     = (float) ($totals['subtotal']         ?? 0);
        $discAmt      = (float) ($totals['discount']['amount'] ?? 0);
        $discType     = $totals['discount']['type']            ?? 'fixed';
        $discValue    = (float) ($totals['discount']['value'] ?? 0);
        $extraCharge  = (float) ($totals['extra_charge']      ?? 0);
        $delivCharge  = (float) ($totals['delivery_charge']   ?? 0);
        $roundOff     = (float) ($totals['round_off']         ?? 0);
        $grandTotal   = (float) ($totals['grand_total']
            ?? max(0, $subtotal - $discAmt + $extraCharge + $delivCharge - $roundOff));

        $otherChargesArr = ['extra_charge' => $extraCharge, 'delivery_charge' => $delivCharge];
        $otherChargeAmt  = $extraCharge + $delivCharge;

        $paymentsJson = [];
        foreach (($input['payments'] ?? []) as $pm) {
            $paymentsJson[strtolower((string) ($pm['method'] ?? ''))] = (float) ($pm['amount'] ?? 0);
        }

        $fields = [
            'customer_id'                => $input['customer_id'],
            'customer_phone'             => $input['customer_phone']       ?? null,
            'customer_name'              => $input['customer_name']        ?? null,
            'product_warehouse_id'       => $input['product_warehouse_id'],
            'sale_date'                  => $input['sale_date'],
            'shipping_date'              => $input['shipping_date']        ?? null,
            'due_date'                   => $input['due_date']             ?? null,
            'subtotal'                   => $subtotal,
            'discount_type'              => $discType,
            'discount_amount'            => $discValue,
            'calculated_discount_amount' => $discAmt,
            'order_status'               => $input['order_status']         ?? 'pending',
            'other_charges'              => ($otherChargesArr),
            'other_charge_amount'        => $otherChargeAmt,
            'round_off_from_total'       => $roundOff,
            'decimal_round_off'          => 0,
            'total'                      => $grandTotal,
            'payments'                   => count($paymentsJson) > 0 ? $paymentsJson : null,
            'paid_amount'                => (float) ($input['paid_amount'] ?? 0),
            'due_amount'                 => (float) ($input['due_amount']  ?? 0),
            'note'                       => $input['note']                 ?? null,
            'reference'                  => $input['reference']            ?? null,
            'delivery_fee'               => $delivCharge,
            'address'                    => $input['address']              ?? null,
            'order_note'                 => $input['note']                 ?? null,
        ];

        return [$fields, $grandTotal, ($otherChargesArr), $otherChargeAmt, $paymentsJson, $totals];
    }

    /**
     * Persist cart items for a quotation (create or re-create).
     */
    private function saveCartItems(ProductOrderQuotation $quotation, array $input): void
    {
        $warehouseId = $input['product_warehouse_id'];

        foreach (($input['cart'] ?? []) as $item) {
            if (empty($item['product_id'])) {
                continue;
            }

            $qty       = (float) ($item['qty']        ?? 1);
            $unitPrice = (float) ($item['unit_price']  ?? 0);
            $discType  = $item['discount_type']         ?? 'fixed';
            $discVal   = (float) ($item['discount_value'] ?? 0);
            $finalPrc  = (float) ($item['final_price']  ?? ($unitPrice * $qty));
            $salePrice = $qty > 0 ? $finalPrc / $qty : $unitPrice;

            $rowDisc = $discType === 'percent'
                ? ($unitPrice * $qty * $discVal / 100)
                : $discVal;

            $product = Product::find($item['product_id']);

            $purchase_price = DB::table('product_purchase_order_products')
                ->where('product_id', $item['product_id'])
                ->orderBy('id', 'desc')
                ->value('purchase_price');
            $price = $product->discount_price ?: $product->price;
            $unit_profit = ($price - $purchase_price);
            $net_profit = ($unit_profit * $item['qty']);

            ProductOrderQuotationProduct::create([
                'product_order_quotation_id' => $quotation->id,
                'product_warehouse_id'       => $warehouseId,
                'product_id'                 => $item['product_id'],
                'variant_id'                 => $item['variant_id']              ?? null,
                'unit_price_id'              => $item['unit_price_id']           ?? null,
                'product_name'               => $item['product_name']            ?? ($product?->name ?? ''),
                'qty'                        => $qty,
                'sale_price'                 => $salePrice,
                'discount_type'              => $discType,
                'discount_amount'            => $discType === 'percent' ? $discVal : $rowDisc,
                'discount_price'             => max(0, $unitPrice - $salePrice),
                'product_price'              => $unitPrice,
                'total_price'                => $finalPrc,
                // 'purchase_price'             => $product?->purchase_price ?? 0,
                'product_image'              => $product?->image          ?? null,
                'creator'                    => Auth::id(),
                'slug'                       => Str::uuid(),
                'status'                     => 'active',
                'unit_profit'                =>  $unit_profit,
                'net_profit'                 =>  $net_profit,
                'purchase_price'             =>  $purchase_price,
            ]);
        }
    }

    // ─── POS data (load quotation into POS form) ────────────────────────────── 

    public function posData(int $id): \Illuminate\Http\JsonResponse
    {
        $quotation = ProductOrderQuotation::with(['order_products.product', 'customer'])
            ->findOrFail($id);

        $imageBase = get_file_url() . '/';

        // Cart items in POS-compatible format
        $cart = $quotation->order_products->map(function ($item) use ($imageBase) {
            $product   = $item->product;
            $unitPrice = (float) ($item->product_price ?? $item->sale_price ?? 0);
            $qty       = (float) ($item->qty ?? 1);
            $finalPrc  = (float) ($item->total_price ?? ($unitPrice * $qty));
            $discType  = $item->discount_type ?? 'fixed';
            $discAmt   = (float) ($item->discount_amount ?? 0);

            return [
                'temp_id'                => 'qt-' . $item->id,
                'product_id'             => $item->product_id,
                'variant_id'             => $item->variant_id,
                'unit_price_id'          => $item->unit_price_id,
                'variant_combination_key'=> null,
                'title'                  => $item->product_name ?? ($product?->name ?? ''),
                'product_name'           => $item->product_name ?? ($product?->name ?? ''),
                'image_url'              => $imageBase . '/' . ($item->product_image ?? ($product?->image ?? '')),
                'qty'                    => $qty,
                'max_qty'                => 9999,
                'unit_price'             => $unitPrice,
                'purchase_price'         => (float) ($product?->purchase_price ?? 0),
                'discount'               => [
                    'type'    => $discType,
                    'value'   => $discAmt,
                    'percent' => $discType === 'percent' ? $discAmt : 0,
                    'fixed'   => $discType === 'fixed'   ? $discAmt : 0,
                ],
                'discount_price' => (float) ($item->discount_price ?? 0),
                'final_price'    => $finalPrc,
                'selected'       => true,
            ];
        })->values()->all();

        // Other charges
        $oc          = $quotation->other_charges;
        if (is_string($oc)) { $oc = json_decode($oc, true) ?: []; }
        $extraCharge = (float) (is_array($oc) ? ($oc['extra_charge'] ?? 0) : 0);
        $delivCharge = (float) (is_array($oc) ? ($oc['delivery_charge'] ?? 0) : ($quotation->delivery_fee ?? 0));

        $totals = [
            'subtotal'         => (float) ($quotation->subtotal ?? 0),
            'discount'         => [
                'type'   => $quotation->discount_type  ?? 'fixed',
                'value'  => (float) ($quotation->discount_amount ?? 0),
                'amount' => (float) ($quotation->calculated_discount_amount ?? 0),
            ],
            'coupon'           => ['code' => '', 'percent' => 0, 'amount' => 0, 'type' => '', 'value' => 0],
            'extra_charge'     => $extraCharge,
            'delivery_charge'  => $delivCharge,
            'round_off'        => (float) ($quotation->round_off_from_total ?? 0),
            'grand_total'      => (float) ($quotation->total ?? 0),
        ];

        // Customer
        $c        = $quotation->customer;
        $customer = [
            'id'                => $quotation->customer_id,
            'name'              => $c?->name  ?? $quotation->customer_name  ?? '',
            'phone'             => $c?->phone ?? $quotation->customer_phone ?? '',
            'email'             => $c?->email ?? '',
            'address'           => $quotation->address ?? $c?->address ?? '',
            'image'             => $c?->image ?? null,
            'available_advance' => (float) ($c?->available_advance ?? 0),
            'due_amount'        => (float) ($c?->due ?? 0),
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'quotation_id'   => $quotation->id,
                'quotation_code' => $quotation->order_code,
                'warehouse_id'   => $quotation->product_warehouse_id,
                'cart'           => $cart,
                'totals'         => $totals,
                'customer'       => $customer,
                'delivery_info'  => [
                    'delivery_method'          => '',
                    'expected_delivery_date'   => now()->addDays(2)->toDateString(),
                    'order_source'             => 'quotation',
                    'order_note'               => $quotation->note ?? '',
                    'outlet_id'                => '',
                    'courier_method'           => null,
                    'courier_method_title'     => '',
                    'delivery_charge_type'     => '',
                ],
                'order_status'   => 'delivered',
                'payments'       => [],
            ],
        ]);
    }

    // ─── Quick status change ──────────────────────────────────────────────────

    public function quickChangeStatus(Request $request)
    {
        $request->validate([
            'ids'    => ['required', 'array'],
            'status' => ['required', 'in:pending,in_review,invoiced,canceled'],
        ]);

        ProductOrderQuotation::whereIn('id', $request->ids)->update(['order_status' => $request->status]);

        return response()->json(['success' => true, 'message' => 'Status updated.']);
    }

    // ─── Convert to Order ─────────────────────────────────────────────────────

    public function convertToOrder(string $slug)
    {
        $quotation = ProductOrderQuotation::with('order_products')->where('slug', $slug)->firstOrFail();

        // Mark as invoiced
        $quotation->update(['order_status' => 'invoiced']);

        return response()->json([
            'success'     => true,
            'message'     => 'Quotation converted. Redirect to create order with this data.',
            'quotation_id' => $quotation->id,
        ]);
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function destroy(string $slug)
    {
        $quotation = ProductOrderQuotation::where('slug', $slug)->firstOrFail();

        if ($quotation->order_status === 'invoiced') {
            return response()->json(['success' => false, 'message' => 'Cannot delete an invoiced quotation.'], 422);
        }

        DB::beginTransaction();
        try {
            $quotation->order_products()->delete();
            $quotation->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Quotation deleted.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
