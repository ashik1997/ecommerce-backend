<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Courier\PathaoController;
use App\Http\Controllers\Courier\SteadfastController;
use App\Http\Controllers\Customer\Models\Customer;
use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use App\Http\Controllers\Inventory\Models\ProductStock;
use App\Http\Controllers\Outlet\Models\Outlet;
use App\Http\Controllers\PackageProductController;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Models\BillingAddress;
use App\Models\District;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductOrderCourierMethod;
use App\Models\ProductOrderProduct;
use App\Models\ProductPurchaseOrderProductUnit;
use App\Models\ProductStockLog;
use App\Models\ProductVariantCombination;
use App\Models\Upazila;
use App\Services\FbMarketing\FbmOrderAttributionBridgeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EcomOrderController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    //  PAGE
    // ─────────────────────────────────────────────────────────────────────────

    public function orderEdit($id)
    {
        $warehouses = ProductWarehouse::where('status', 'active')
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('backend.product_order_management.ecom_order_edit', compact('id', 'warehouses'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  ORDER INFO  (GET /ecommerce/order-info/{id})
    // ─────────────────────────────────────────────────────────────────────────

    public function orderInfo($id)
    {
        $order = ProductOrder::with(['products', 'customer'])->findOrFail($id);

        // ── address object ──
        // $address = $this->buildAddressObject($order);
        $address = $order->address_json;

        // ── product lines ──
        $products = $order->products->map(function ($p) {
            $package_controller = new PackageProductController();
            $product = Product::find($p->product_id);
            if (! $product) {
                return null;
            }

            $selected_variant = ProductVariantCombination::where('id', $p->variant_id)->first();
            $selected_variant_info = $selected_variant->variant_values ?? null;

            $stock = $product->stock ?? $product->stocks()->sum('qty') ?? 0;
            $variant_info = $this->buildSimpleProductStockInfo($product);
            if ($product->has_variant == 1) {
                $variant_info = $package_controller->prepareProductVariantMatrix($product);
                $stock = $variant_info['stock'] ?? 0;
            } else {
                $stock = $variant_info['stock'] ?? $stock;
            }

            $variant_string = '';
            if ($selected_variant_info) {
                $variant_string = implode(' • ', $selected_variant_info);
            }

            $warehouseName = $p->product_warehouse_id
                ? DB::table('product_warehouses')->where('id', $p->product_warehouse_id)->value('title')
                : null;
            $roomName = $p->product_warehouse_room_id
                ? DB::table('product_warehouse_rooms')->where('id', $p->product_warehouse_room_id)->value('title')
                : null;
            $cartoonName = $p->product_warehouse_room_cartoon_id
                ? DB::table('product_warehouse_room_cartoons')->where('id', $p->product_warehouse_room_cartoon_id)->value('title')
                : null;

            $data =  [
                'id'                => $p->id,
                'variant_id'        => (int)$p->variant_id,
                'combination_id'    => (int)$p->variant_id,
                'product_id'        => $p->product_id,
                'product_name'      => $p->product_name ? $p->product_name : $product->name,
                'product_price'     => $p->product_price, //(float) ($p->sale_price   ?? 0),
                'product_image'     => $p->product_image,
                'discount_amount'   => (float) ($p->discount_amount ?? 0),
                'qty'               => (int)   ($p->qty ?? 1),
                'stock'             => (int) $stock,
                'variant_info'      => $variant_info,
                'price_unit'        => $p->price_unit,
                'variant'           => $variant_string,
                'selected_variant_info' => $selected_variant_info,
                'warehouse_id'      => $p->product_warehouse_id ? (int) $p->product_warehouse_id : null,
                'warehouse_room_id' => $p->product_warehouse_room_id ? (int) $p->product_warehouse_room_id : null,
                'warehouse_carton_id' => $p->product_warehouse_room_cartoon_id ? (int) $p->product_warehouse_room_cartoon_id : null,
                'warehouse_name'    => $warehouseName,
                'room_name'         => $roomName,
                'carton_name'       => $cartoonName,
            ];
            return $data;
            // return [
            //     'id'              => $p->id,
            //     'product_id'      => $p->product_id,
            //     'product_name'    => $p->product_name,
            //     'product_image'   => $p->product_image,
            //     'product_price'   => (float) ($p->sale_price   ?? 0),
            //     'discount_amount' => (float) ($p->discount_amount ?? 0),
            //     'qty'             => (int)   ($p->qty ?? 1),
            //     'price_unit'      => $p->price_unit,
            // ];
        })->filter()->values();

        // ── payments  →  { cash: 100, bkash: 50, … } ──
        $rawPayments = is_array($order->payments)
            ? $order->payments
            : (json_decode($order->payments, true) ?: []);

        $payments = collect($rawPayments)
            ->except(['advance_used', 'total_paid', 'total_due'])
            ->toArray();

        // Fall back to payment_method column when no breakdown is stored
        if (empty($payments) && $order->payment_method) {
            $payments[$order->payment_method] = (float) $order->paid_amount;
        }

        // ── delivery_info ──
        $deliveryInfo = is_array($order->delivery_info)
            ? $order->delivery_info
            : (json_decode($order->delivery_info, true) ?: []);

        return response()->json([
            'order_code'           => $order->order_code,
            'sale_date'            => $order->sale_date,
            'order_note'           => $order->note ?? $order->order_note,
            'order_status'         => $order->order_status,
            'salesman_id'          => $order->salesman_id,
            'affiliate_id'         => $order->affiliate_id,
            'affiliate_code'       => $order->affiliate_code,
            'order_source'         => $order->order_source,
            'customer_name'        => $order->customer_name,
            'customer_phone'       => $order->customer_phone,
            'address'              => $address,
            'products'             => $products,
            'discount_type'        => $order->discount_type,
            'discount_amount'      => (float) ($order->discount_amount ?? 0),
            'coupon_discount_amount' => (float) ($order->coupon_discount_amount ?? 0),
            'coupon'               => $order->coupon ?? '',
            'other_charge_amount'  => (float) ($order->other_charge_amount ?? 0),
            'delivery_fee'         => (float) ($order->delivery_fee ?? 0),
            'round_off_from_total' => (float) ($order->round_off_from_total ?? 0),
            'due_date'             => $order->due_date,
            'paid_amount'          => (float) ($order->paid_amount ?? 0),
            'total'                => (float) ($order->total ?? 0),
            'payments'             => $payments,
            'delivery_info'        => $deliveryInfo,
            'slug'                 => $order->slug,
        ]);
    }

    protected function buildSimpleProductStockInfo(Product $product): array
    {
        $stockRows = ProductStock::query()
            ->where('product_stocks.product_id', $product->id)
            ->where('product_stocks.status', 'active')
            ->where(function ($query) {
                $query->whereNull('product_stocks.variant_combination_id')
                    ->orWhere('product_stocks.has_variant', 0);
            })
            ->leftJoin('product_warehouses', 'product_stocks.product_warehouse_id', '=', 'product_warehouses.id')
            ->leftJoin('product_warehouse_rooms', 'product_stocks.product_warehouse_room_id', '=', 'product_warehouse_rooms.id')
            ->leftJoin('product_warehouse_room_cartoons', 'product_stocks.product_warehouse_room_cartoon_id', '=', 'product_warehouse_room_cartoons.id')
            ->select(
                'product_stocks.qty as stock',
                'product_warehouses.id as warehouse_id',
                'product_warehouses.title as warehouse_name',
                'product_warehouse_rooms.id as room_id',
                'product_warehouse_rooms.title as room_name',
                'product_warehouse_room_cartoons.id as cartoon_id',
                'product_warehouse_room_cartoons.title as cartoon_name'
            )
            ->get();

        $totalStock = (int) $stockRows->sum('stock');
        if ($totalStock <= 0) {
            $totalStock = (int) ($product->stock ?? 0);
        }

        $warehouseStocks = $stockRows
            ->groupBy(fn($row) => $row->warehouse_id ?? '')
            ->filter(fn($group, $id) => $id !== '' && $group->first()->warehouse_name)
            ->map(fn($group) => [
                'id'    => (int) $group->first()->warehouse_id,
                'name'  => $group->first()->warehouse_name,
                'stock' => (int) $group->sum('stock'),
            ])
            ->values()
            ->toArray();

        $roomStocks = $stockRows
            ->groupBy(fn($row) => ($row->warehouse_id ?? '') . '_' . ($row->room_id ?? ''))
            ->filter(fn($group) => $group->first()->warehouse_id && $group->first()->room_id)
            ->map(fn($group) => [
                'id'           => (int) $group->first()->room_id,
                'warehouse_id' => (int) $group->first()->warehouse_id,
                'name'         => $group->first()->room_name,
                'stock'        => (int) $group->sum('stock'),
            ])
            ->values()
            ->toArray();

        $cartoonStocks = $stockRows
            ->groupBy(fn($row) => ($row->warehouse_id ?? '') . '_' . ($row->room_id ?? '') . '_' . ($row->cartoon_id ?? ''))
            ->filter(fn($group) => $group->first()->warehouse_id && $group->first()->room_id && $group->first()->cartoon_id)
            ->map(fn($group) => [
                'id'           => (int) $group->first()->cartoon_id,
                'warehouse_id' => (int) $group->first()->warehouse_id,
                'room_id'      => (int) $group->first()->room_id,
                'name'         => $group->first()->cartoon_name ?: 'Carton #' . $group->first()->cartoon_id,
                'stock'        => (int) $group->sum('stock'),
            ])
            ->values()
            ->toArray();

        return [
            'variant_type'    => 'simple',
            'product'         => [
                'id'              => $product->id,
                'name'            => $product->name,
                'slug'            => $product->slug,
                'price'           => $product->price,
                'discount_price'  => $product->discount_price,
                'effective_price' => $product->discount_price && $product->discount_price > 0
                    ? $product->discount_price
                    : $product->price,
                'image_url'       => $product->image ? get_file_url() . '/' . $product->image : null,
                'total_stock'     => $totalStock,
            ],
            'combinations'      => [],
            'legacy_variants'   => [],
            'colors'            => [],
            'sizes'             => [],
            'other_variants'    => [],
            'warehouse_stocks'  => $warehouseStocks,
            'room_stocks'       => $roomStocks,
            'cartoon_stocks'    => $cartoonStocks,
            'stock'             => $totalStock,
        ];
    }

    /**
     * Build a normalised address object from several possible storage locations:
     *   1. billing_addresses table (ecommerce orders from the website)
     *   2. JSON-encoded address column
     *   3. delivery_info JSON
     *   4. raw customer / order fields
     */
    protected function buildAddressObject(ProductOrder $order): array
    {
        // 1 ── billing_addresses table
        $billing = BillingAddress::where('order_id', $order->id)->first();
        if ($billing) {
            $district = $billing->district_id
                ? District::find($billing->district_id)
                : null;

            return [
                'name'          => $billing->full_name,
                'phone'         => $billing->phone,
                'email'         => optional($order->customer)->email ?? '',
                'district_name' => $district ? $district->name : '',
                'district_id'   => $billing->district_id,
                'upozilla_name' => '',
                'upozilla_id'   => null,
                'thana'         => $billing->thana,
                'post_office'   => $billing->post_code,
                'address'       => $billing->address,
            ];
        }

        // 2 ── address column might be a JSON string with full details
        if ($order->address && is_string($order->address)) {
            $decoded = json_decode($order->address, true);
            if (is_array($decoded) && isset($decoded['address'])) {
                return array_merge([
                    'name' => '',
                    'phone' => '',
                    'email' => '',
                    'district_name' => '',
                    'district_id' => null,
                    'upozilla_name' => '',
                    'upozilla_id' => null,
                    'thana' => '',
                    'post_office' => '',
                ], $decoded);
            }
        }

        // 3 ── request_data may carry the original ecommerce form fields
        $requestData = is_array($order->request_data)
            ? $order->request_data
            : (json_decode($order->request_data, true) ?: []);

        if (!empty($requestData)) {
            $districtName = $requestData['district_name']  ?? $requestData['district']  ?? '';
            $upazilaName  = $requestData['upozilla_name']  ?? $requestData['upazila']   ?? '';

            return [
                'name'          => $requestData['name']         ?? optional($order->customer)->name  ?? $order->customer_name,
                'phone'         => $requestData['phone']        ?? optional($order->customer)->phone ?? $order->customer_phone,
                'email'         => $requestData['email']        ?? optional($order->customer)->email ?? '',
                'district_name' => $districtName,
                'district_id'   => $requestData['district_id'] ?? null,
                'upozilla_name' => $upazilaName,
                'upozilla_id'   => $requestData['upazila_id']  ?? null,
                'thana'         => $requestData['thana']        ?? '',
                'post_office'   => $requestData['post_office']  ?? '',
                'address'       => $requestData['address']      ?? (is_string($order->address) ? $order->address : ''),
            ];
        }

        // 4 ── plain fallback
        $customer = $order->customer;
        return [
            'name'          => optional($customer)->name  ?? $order->customer_name  ?? '',
            'phone'         => optional($customer)->phone ?? $order->customer_phone ?? '',
            'email'         => optional($customer)->email ?? '',
            'district_name' => '',
            'district_id'   => null,
            'upozilla_name' => '',
            'upozilla_id'   => null,
            'thana'         => '',
            'post_office'   => '',
            'address'       => is_string($order->address) ? $order->address : '',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  UPDATE ORDER  (POST /ecommerce/order-update/{id})
    // ─────────────────────────────────────────────────────────────────────────

    public function updateOrder(Request $request, $id)
    {
        $order = ProductOrder::with(['products'])->findOrFail($id);
        $previousStatus = $order->order_status;

        $customerData = $request->input('customer', []);
        $productsList = $request->input('products', []);
        $summary      = $request->input('summary',  []);
        $paymentsIn   = $request->input('payments', []);
        $delivery     = $request->input('delivery', []);
        $orderStatus  = $request->input('order_status', $order->order_status);
        $orderNote    = $request->input('order_note', $order->order_note);
        $salesmanId   = $request->input('salesman_id', $order->salesman_id ?: $order->creator);
        $affiliateCode = $request->input('affiliate_code', $order->affiliate_code);

        // ── recalculate totals server-side ──────────────────────────────────
        $subtotal = 0;
        foreach ($productsList as $item) {
            $unitPrice = (float) ($item['unit']            ?? 0);
            $discAmt   = (float) ($item['discount_amount'] ?? 0);
            $qty       = max(1,   (int) ($item['qty']      ?? 1));
            $subtotal += max(0, $unitPrice - $discAmt) * $qty;
        }

        $discountType   = $summary['discountType']   ?? '';
        $discountValue  = (float) ($summary['discountValue']  ?? 0);
        $couponDiscount = (float) ($summary['couponDiscount'] ?? 0);
        $extraCharge    = (float) ($summary['extraCharge']    ?? 0);
        $deliveryCharge = (float) ($summary['deliveryCharge'] ?? 0);
        $roundOff       = (float) ($summary['roundOff']       ?? 0);

        $discountAmount = match ($discountType) {
            'percent' => $subtotal * ($discountValue / 100),
            'fixed'   => $discountValue,
            default   => 0,
        };

        $grandTotal = $subtotal
            - $discountAmount
            - $couponDiscount
            + $extraCharge
            + $deliveryCharge
            - $roundOff;

        // ── normalise payments ──────────────────────────────────────────────
        $paymentsArr  = [];
        $paymentTotal = 0;
        foreach ($paymentsIn as $key => $amount) {
            $amt = (float) $amount;
            if ($amt > 0) {
                $paymentsArr[$key] = $amt;
                $paymentTotal     += $amt;
            }
        }
        $dueAmount = max(0, $grandTotal - $paymentTotal);

        // ── customer fields ─────────────────────────────────────────────────
        $customerName  = $customerData['name']  ?? $order->customer_name;
        $customerPhone = $customerData['phone'] ?? $order->customer_phone;
        $customerEmail = $customerData['email'] ?? null;
        $districtId    = $customerData['district_id']  ?? null;
        $upazilaId     = $customerData['upazila_id']   ?? null;
        $thana         = $customerData['thana']         ?? null;
        $postOffice    = $customerData['post_office']   ?? null;
        $addressLine   = $customerData['address']       ?? $order->address;
        $orderSource   = $customerData['order_source']  ?? $order->order_source;

        // Resolve district / upazila names for address_json
        $districtName = null;
        $upazilaName  = null;
        if ($districtId) {
            $district     = \App\Models\District::find($districtId);
            $districtName = $district?->name;
        }
        if ($upazilaId) {
            $upazila     = \App\Models\Upazila::find($upazilaId);
            $upazilaName = $upazila?->name;
        }

        DB::beginTransaction();
        try {

            // ── update customer record ──────────────────────────────────────
            if ($order->customer_id && $order->customer_id > 1) {
                $cust = Customer::find($order->customer_id);
                if ($cust) {
                    if (!empty($customerName))  $cust->name    = $customerName;
                    if (!empty($customerPhone)) $cust->phone   = $customerPhone;
                    if (!empty($addressLine))   $cust->address = $addressLine;
                    $cust->save();
                }
            }

            // ── update billing address row ──────────────────────────────────
            $billing = BillingAddress::where('order_id', $order->id)->first();
            if ($billing) {
                $billing->update([
                    'full_name'   => $customerName,
                    'phone'       => $customerPhone,
                    'address'     => $addressLine,
                    'district_id' => $districtId  ?? $billing->district_id,
                    'upazila_id'  => $upazilaId   ?? $billing->upazila_id,
                    'thana'       => $thana        ?? $billing->thana,
                    'post_code'   => $postOffice   ?? $billing->post_code,
                ]);
            }

            // ── build address_json (mirrors the ecommerce order structure) ──
            $addressJson = [
                'name'              => $customerName,
                'phone'             => $customerPhone,
                'phone_normalized'  => '880' . ltrim($customerPhone, '0'),
                'email'             => $customerEmail,
                'address'           => $addressLine,
                'district_id'       => $districtId,
                'upozilla_id'       => $upazilaId,
                'thana'             => $thana,
                'post_office'       => $postOffice,
                'district_name'     => $districtName,
                'upozilla_name'     => $upazilaName,
                'delivery_location' => $districtId == 11 ? 'inside_dhaka' : 'outside_dhaka', // adjust district id as needed
            ];

            // ── build payments JSON (mirrors the original payments structure) ─
            $paymentsJson = array_merge(
                [
                    'cash'                => '0',
                    'bkash'               => '0',
                    'rocket'              => '0',
                    'nogod'               => '0',
                    'credit'              => '0',
                    'cheque'              => '0',
                    'bank'                => '0',
                    'gateway'             => '0',
                    'advance_adjustment'  => '0',
                ],
                array_map('strval', $paymentsArr),
                [
                    'total_paid' => (string) $paymentTotal,
                    'total_due'  => (string) $dueAmount,
                ]
            );

            // ── build other_charges JSON (mirrors original format) ──────────
            $otherChargesJson = [];
            if ($deliveryCharge > 0) {
                $otherChargesJson[] = [
                    'title'  => 'shipping',
                    'type'   => 'fixed',
                    'amount' => $deliveryCharge,
                ];
            }
            if ($extraCharge > 0) {
                $otherChargesJson[] = [
                    'title'  => 'extra charge',
                    'type'   => 'fixed',
                    'amount' => $extraCharge,
                ];
            }

            // ── build delivery_info ─────────────────────────────────────────
            $existingDelivery = is_array($order->delivery_info)
                ? $order->delivery_info
                : (json_decode($order->delivery_info, true) ?: []);

            $courierMethod = null;
            if(isset($delivery['courier']) && $delivery['courier'] != null) {
                $courierMethod = ProductOrderCourierMethod::where('title', 'like', '%' . $delivery['courier'] . '%')->first();
            }

            $newDeliveryInfo = array_merge($existingDelivery, array_filter([
                'delivery_method'        => $delivery['method']         ?? null,
                'expected_delivery_date' => $delivery['expectedDate']   ?? null,
                'courier_method'         => strtolower($courierMethod->title ?? '')          ?? null,
                'courier_method_id'      => $courierMethod->id             ?? null,
                'courier_address'        => $delivery['courierAddress'] ?? null,
                'order_note'             => $delivery['courierNote']    ?? null,
                'outlet'                 => $delivery['outlet']         ?? null,
            ], fn($v) => $v !== null));

            // ── update order header ─────────────────────────────────────────
            $order->order_source               = $orderSource;
            $order->customer_name              = $customerName;
            $order->customer_phone             = $customerPhone;
            $order->address                    = $addressLine;
            $order->address_json               = $addressJson;
            $order->order_note                 = $orderNote;
            $order->note                       = $orderNote;

            $order->subtotal                   = $subtotal;
            $order->discount_type              = $discountType ?: null;
            $order->discount_amount            = $discountValue;
            $order->coupon                     = $summary['couponCode'] ?? null;
            $order->coupon_discount_amount     = $couponDiscount;
            $order->calculated_discount_amount = $discountAmount;
            $order->other_charge_amount        = $extraCharge;
            $order->delivery_fee               = $deliveryCharge;
            $order->other_charges              = ($otherChargesJson);
            $order->round_off_from_total       = $roundOff;
            $order->decimal_round_off          = $roundOff;
            $order->total                      = $grandTotal;
            $order->paid_amount                = $paymentTotal;
            $order->due_amount                 = $dueAmount;
            $order->payments                   = ($paymentsJson);
            $order->payment_status             = $paymentTotal >= $grandTotal ? '1' : '0';

            // $order->order_status               = $orderStatus;
            $order->due_date                   = $delivery['expectedDate'] ?? $order->due_date;
            $order->delivery_info              = $newDeliveryInfo;
            $order->salesman_id                = $salesmanId;
            $order->affiliate_code             = $affiliateCode;
            if ($affiliateCode) {
                $affiliate = \App\Models\Affiliate::where('code', $affiliateCode)->where('status', 'active')->first();
                $order->affiliate_id = $affiliate ? $affiliate->id : $order->affiliate_id;
            }
            $order->updated_at                 = Carbon::now();
            $order->save();

            $approve_state = ['accepted', 'invoiced', 'processing', 'delivered'];
            $cancel_state = ['returned', 'pending', 'cancelled'];
            $reapplyApprovedEffects = false;

            if (in_array($previousStatus, $approve_state, true) && (int) ($order->is_stock_deducted ?? 0) === 1) {
                if ((int) ($order->is_accounting_posted ?? 0) === 1) {
                    $result = ecommerce_account_reverse($order, 'Approved ecommerce order edited');
                    if (($result['success'] ?? false) === true) {
                        $this->setOrderFlag($order, 'is_accounting_posted', 0);
                    }
                }

                $this->rollbackEcomOrderEffects($order);
                $reapplyApprovedEffects = in_array($orderStatus, $approve_state, true);
            }

            // ── rebuild product_order_products ─────────────────────────────
            ProductOrderProduct::where('product_order_id', $order->id)->delete();

            foreach ($productsList as $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                if (!$productId) continue;

                $product = Product::find($productId);
                if (!$product) continue;

                $unitPrice     = (float) ($item['unit']            ?? 0);
                $discAmt       = (float) ($item['discount_amount'] ?? 0);
                $qty           = max(1,   (int) ($item['qty']      ?? 1));
                $lineTotal     = max(0, ($unitPrice - $discAmt)) * $qty;
                $combinationId = (int) ($item['combination_id'] ?? 0) ?: null;
                $variantLabel  = $item['variant'] ?? null;
                $barcode = $item['barcode'] ?? null;


                // Resolve combination_key from the combination id if available
                // combination_key is stored as price_unit in the original order products
                // e.g. "Green-L" — but the frontend now sends the display label e.g. "yellow • M"
                // We prefer the combination_key (slug form) for price_unit to stay consistent
                $priceUnit = $variantLabel; // fallback to display label
                if ($combinationId) {
                    $combo = ProductVariantCombination::find($combinationId);
                    if ($combo) {
                        $priceUnit = $combo->combination_key; // e.g. "yellow-M"
                    }
                }

                // Resolve warehouse ids from the whModal assignment if sent
                // (optional — only present if user assigned in the modal)
                $warehouseId = (int) ($item['warehouse_id']        ?? 0) ?: null;
                $roomId      = (int) ($item['warehouse_room_id']   ?? 0) ?: null;
                $cartonId    = (int) ($item['warehouse_carton_id'] ?? 0) ?: null;
                $supplierId  = (int) ($item['supplier_id'] ?? 0) ?: null;
                $product_purchase_order_id = null;
                $unit = null;

                if ($barcode) {
                    $unit_query = ProductPurchaseOrderProductUnit::where([
                        'product_id' => $product->id,
                        'unit_status' => 'instock',
                        'code' => $barcode,
                    ]);
                    if ($combinationId) {
                        $unit_query->where('variant_combination_id', $combinationId);
                    }
                    $unit = $unit_query->first();

                    if ($unit) {
                        $productPurchaseOrderProduct = $unit->productPurchaseOrderProduct;
                        $warehouseId = $unit->product_warehouse_id;
                        $roomId = $productPurchaseOrderProduct->product_warehouse_room_id ?? null;
                        $cartonId = $productPurchaseOrderProduct->product_warehouse_room_cartoon_id ?? null;
                        $supplierId = $productPurchaseOrderProduct->product_supplier_id ?? null;
                        $product_purchase_order_id = $unit->product_purchase_order_id;
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Barcode not found for product: ' . $product->name . ' with barcode: ' . $barcode,
                        ], 404);
                    }
                }

                $product_order_product = ProductOrderProduct::create([
                    'product_order_id'                  => $order->id,
                    'product_id'                        => $product->id,
                    'variant_id'                        => $combinationId,
                    'product_name'                      => $product->name,
                    'product_image'                     => $item['product_image'] ?? $product->image,
                    'qty'                               => $qty,
                    'sale_price'                        => $lineTotal,
                    'discount_type'                     => $discAmt > 0 ? 'fixed' : null,
                    'discount_amount'                   => $discAmt,
                    'total_price'                       => $lineTotal,
                    'product_price'                     => $unitPrice,
                    'price_unit'                        => $priceUnit,
                    'product_warehouse_id'              => $warehouseId,
                    'product_warehouse_room_id'         => $roomId,
                    'product_warehouse_room_cartoon_id' => $cartonId,
                    'slug'                              => Str::orderedUuid(),
                    'creator'                           => auth()->id(),
                    'tax'                               => 0,
                    'purchase_price'                    => $unit->price ?? null,
                    'discount_price'                    => 0,
                    'product_supplier_id'               => $supplierId,
                    'unit_price_id'                     => null,
                    'product_purchase_order_id'         => null,
                    'product_purchase_order_product_id' => null,
                    'product_purchase_order_product_unit_id' => null,
                ]);

                // ── optionally update order-level warehouse if single product ──
                // (mirrors how ecommerce sets product_warehouse_id on the order)
                if ($warehouseId && count($productsList) === 1) {
                    $order->product_warehouse_id              = $warehouseId;
                    $order->product_warehouse_room_id         = $roomId;
                    $order->product_warehouse_room_cartoon_id = $cartonId;
                    $order->save();
                }
            }

            // Handle state transitions: only apply side effects when crossing
            // between cancel_state <-> approve_state.
            if (in_array($orderStatus, $approve_state, true)) {
                $order->order_status = $orderStatus;
                $order->save();

                if (in_array($previousStatus, $cancel_state, true) || $reapplyApprovedEffects) {
                    // Moving from pending/returned/cancelled → accepted/processing/delivered
                    // Apply stock, unit and accounting effects once.
                    if ((int) ($order->is_stock_deducted ?? 0) !== 1) {
                        $this->applyEcomOrderEffects($order, $productsList);
                    }

                    // Trigger courier only if not already couriered
                    $this->maybeCreateCourierOrder($order);

                    $order->refresh();
                    if ($this->isEcommerceAccountingReady($order) && (int) ($order->is_accounting_posted ?? 0) !== 1) {
                        $result = ecommerce_account_create($order);
                        if (($result['success'] ?? false) === true) {
                            $this->setOrderFlag($order, 'is_accounting_posted', 1);
                            $this->appendOrderTrack($order, 'accounting_posted');
                            $order->save();
                        }
                    }

                    if ($order->customer_id) {
                        calc_customer_balance($order->customer_id);
                    }
                }

                $order->refresh();
                if ($this->isEcommerceAccountingReady($order) && (int) ($order->is_accounting_posted ?? 0) !== 1) {
                    $result = ecommerce_account_create($order);
                    if (($result['success'] ?? false) === true) {
                        $this->setOrderFlag($order, 'is_accounting_posted', 1);
                        $this->appendOrderTrack($order, 'accounting_posted');
                        $order->save();
                    }

                    if ($order->customer_id) {
                        calc_customer_balance($order->customer_id);
                    }
                }
            } elseif (in_array($orderStatus, $cancel_state, true)) {
                if (in_array($previousStatus, $approve_state, true)) {
                    // Moving from accepted/processing/delivered → pending/returned/cancelled
                    // Reverse stock / unit / accounting effects.
                    $this->rollbackEcomOrderEffects($order);

                    if ((int) ($order->is_accounting_posted ?? 0) === 1) {
                        $result = ecommerce_account_reverse($order, 'Order moved back to cancel state');
                        if (($result['success'] ?? false) === true) {
                            $this->setOrderFlag($order, 'is_accounting_posted', 0);
                            $this->appendOrderTrack($order, 'accounting_reversed');
                            $order->save();
                        }
                    }

                    if ($order->customer_id) {
                        calc_customer_balance($order->customer_id);
                    }
                }

                $order->order_status = $orderStatus;
                $order->save();
            }

            DB::commit();

            FbmOrderAttributionBridgeService::tryReconcileProductOrderById((int) $order->id, 'ecommerce_order_update');

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully.',
                'data'    => [
                    'order_id'      => $order->id,
                    'order_code'    => $order->order_code,
                    'grand_total'   => $grandTotal,
                    'paid_amount'   => $paymentTotal,
                    'due_amount'    => $dueAmount,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Order update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PRODUCT SEARCH  (GET /ecommerce/products/search)
    // ─────────────────────────────────────────────────────────────────────────

    public function searchProducts(Request $request)
    {
        $q = trim($request->get('q', ''));

        $query = Product::where('status', 1);

        if ($q !== '') {
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'LIKE', "%{$q}%")
                    ->orWhere('code', 'LIKE', "%{$q}%");
            });
        }

        $products = $query->orderBy('name')->limit(10)->get(['id', 'name', 'slug', 'price', 'discount_price', 'image', 'stock', 'has_variant']);

        $fileUrl = get_file_url();

        $package_controller = new PackageProductController();
        $data = $products->map(function ($p) use ($fileUrl, $package_controller) {
            $variant_info = $p->has_variant == 1
                ? $package_controller->prepareProductVariantMatrix($p)
                : $this->buildSimpleProductStockInfo($p);
            $stock = $variant_info['stock'] ?? 0;
            return [
                'id'    => $p->id,
                'name'  => $p->name,
                'price' => (float) ($p->discount_price ?: $p->price),
                'image' => $p->image ? ($fileUrl . '/' . $p->image) : null,
                'stock' => (int) $stock,
                'variant_info' => $variant_info,
            ];
        });


        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GEO  (GET /ecommerce/districts  |  GET /ecommerce/upazilas)
    // ─────────────────────────────────────────────────────────────────────────

    public function districts()
    {
        $districts = District::orderBy('name')->get(['id', 'name']);
        return response()->json(['success' => true, 'data' => $districts]);
    }

    public function upazilas(Request $request)
    {
        $query = Upazila::query();

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->get('district_id'));
        }

        $upazilas = $query->orderBy('name')->get(['id', 'name', 'district_id']);
        return response()->json(['success' => true, 'data' => $upazilas]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PAYMENT METHODS  (GET /ecommerce/payment-methods)
    // ─────────────────────────────────────────────────────────────────────────

    public function ecomPaymentMethods()
    {
        // Delegate to DesktopPosController so we share the same DB-backed list
        return app(DesktopPosController::class)->getPaymentMethods(request());
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  WAREHOUSES  (GET /ecommerce/warehouses)
    // ─────────────────────────────────────────────────────────────────────────

    public function warehouses()
    {
        $warehouses = ProductWarehouse::where('status', 'active')
            ->orderBy('title')
            ->get(['id', 'title']);

        return response()->json(['success' => true, 'data' => $warehouses]);
    }

    public function outlets()
    {
        $outlets = Outlet::query()
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere('status', 1);
            })
            ->orderBy('title')
            ->get(['id', 'title', 'address', 'contact_number_1']);

        return response()->json(['success' => true, 'data' => $outlets]);
    }

    /**
     * Apply stock, unit and related side effects when an ecommerce order
     * transitions from a non-approved state into an approved state.
     *
     * Mirrors the core logic from DesktopPosController@createOrder, but operates
     * on an existing ProductOrder + the edited products list (for barcode/unit mapping).
     */
    protected function applyEcomOrderEffects(ProductOrder $order, array $productsList): void
    {
        if ((int) ($order->is_stock_deducted ?? 0) === 1) {
            return;
        }

        $order->loadMissing('order_products');
        $orderProducts = $order->order_products->values();
        $totalPurchasePrice = 0;
        $totalSalesPrice = 0;

        foreach ($orderProducts as $index => $orderProduct) {
            if (!isset($productsList[$index])) {
                continue;
            }

            $item = $productsList[$index];

            $qty = (float) ($orderProduct->qty ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $product = Product::find($orderProduct->product_id);
            if (!$product) {
                continue;
            }

            $variantId   = $orderProduct->variant_id ?: null;
            $warehouseId = $orderProduct->product_warehouse_id ?: $order->product_warehouse_id;
            $roomId      = $orderProduct->product_warehouse_room_id ?: null;
            $cartonId    = $orderProduct->product_warehouse_room_cartoon_id ?: null;
            $unitCode    = $item['barcode'] ?? null;

            if (!$warehouseId) {
                throw new \RuntimeException('Warehouse is required for product: ' . $product->name);
            }

            $this->assertEcomStockAvailable($product->id, $variantId, $warehouseId, $roomId, $cartonId, $qty);

            // Allocate purchase units in FIFO style, preferring an exact barcode match when present
            $remaining = (int) $qty;
            $allocatedUnits = [];
            $purchaseCost = 0;
            while ($remaining > 0) {
                if ($unitCode && $remaining === (int) $qty) {
                    // First unit: try to match the scanned barcode
                    $unitQuery = ProductPurchaseOrderProductUnit::where('code', $unitCode)
                        ->where('product_id', $product->id)
                        ->where('unit_status', 'instock');
                    if ($variantId) {
                        $unitQuery->where('variant_combination_id', $variantId);
                    }
                    if ($warehouseId) {
                        $unitQuery->where('product_warehouse_id', $warehouseId);
                    }
                } else {
                    // Subsequent units: generic FIFO by product (and variant/warehouse when available)
                    $unitQuery = ProductPurchaseOrderProductUnit::where('product_id', $product->id)
                        ->where('unit_status', 'instock');

                    if ($variantId) {
                        $unitQuery->where('variant_combination_id', $variantId);
                    }
                    if ($warehouseId) {
                        $unitQuery->where('product_warehouse_id', $warehouseId);
                    }
                }

                $unit = $unitQuery->lockForUpdate()->orderBy('id')->first();

                if (!$unit) {
                    throw new \RuntimeException('Insufficient purchase units for product: ' . $product->name);
                }

                // Update order product with warehouse room/carton/supplier info from the purchase unit
                $purchaseOrderProduct = $unit->productPurchaseOrderProduct ?? null;
                if ($purchaseOrderProduct) {
                    $orderProduct->product_warehouse_room_id = $purchaseOrderProduct->product_warehouse_room_id ?? $orderProduct->product_warehouse_room_id;
                    $orderProduct->product_warehouse_room_cartoon_id = $purchaseOrderProduct->product_warehouse_room_cartoon_id ?? $orderProduct->product_warehouse_room_cartoon_id;
                    $orderProduct->product_supplier_id = $purchaseOrderProduct->product_supplier_id ?? $orderProduct->product_supplier_id;
                }

                $unit->update([
                    'sale_id'                  => $order->id,
                    'unit_status'              => 'sold',
                    'product_order_product_id' => $orderProduct->id,
                    'updated_at'               => Carbon::now(),
                ]);

                $purchaseCost += (float) ($unit->price ?? 0);
                $allocatedUnits[] = [
                    'unit_id' => $unit->id,
                    'code' => $unit->code,
                    'purchase_order_id' => $unit->product_purchase_order_id,
                    'purchase_order_product_id' => $unit->product_purchase_order_product_id,
                    'purchase_price' => (float) ($unit->price ?? 0),
                ];

                if (count($allocatedUnits) === 1) {
                    $orderProduct->product_purchase_order_id = $unit->product_purchase_order_id;
                    $orderProduct->product_purchase_order_product_id = $unit->product_purchase_order_product_id;
                    $orderProduct->product_purchase_order_product_unit_id = $unit->id;
                    $orderProduct->unit_price_id = $unit->id;
                }

                $remaining--;
            }

            $unitPurchasePrice = $qty > 0 ? round($purchaseCost / $qty, 2) : 0;
            $lineSalesTotal = (float) ($orderProduct->total_price ?? $orderProduct->sale_price ?? 0);
            $lineProfit = round($lineSalesTotal - $purchaseCost, 2);
            $orderProduct->purchase_price = $unitPurchasePrice;
            $orderProduct->unit_profit = round((($lineSalesTotal / max($qty, 1)) - $unitPurchasePrice), 2);
            $orderProduct->net_profit = $lineProfit;
            $orderProduct->cost_method = 'fifo';
            $orderProduct->item_meta = array_merge((array) ($orderProduct->item_meta ?? []), [
                'stock_allocations' => $allocatedUnits,
            ]);
            $orderProduct->save();

            $totalPurchasePrice += $purchaseCost;
            $totalSalesPrice += $lineSalesTotal;

            // Decrement aggregate product & variant stock
            if ($variantId) {
                $variant = ProductVariantCombination::find($variantId);
                if ($variant) {
                    $variant->decrement('stock', $qty);
                }
            }

            $product->decrement('stock', $qty);

            $this->decrementEcomProductStock($product->id, $variantId, $warehouseId, $roomId, $cartonId, $qty);

            // Log stock movement
            ProductStockLog::create([
                'product_id'             => $product->id,
                'warehouse_id'           => $warehouseId,
                'product_name'           => $product->name,
                'product_sales_id'       => $order->id,
                'variant_combination_id' => $variantId,
                'quantity'               => $qty,
                'type'                   => 'sales',
                'has_variant'            => $variantId ? 1 : 0,
                'creator'                => Auth::id(),
                'slug'                   => uniqid() . time(),
                'status'                 => 1,
                'created_at'             => Carbon::now(),
                'updated_at'             => Carbon::now(),
            ]);
        }

        $order->total_purchase_price = round($totalPurchasePrice, 2);
        $order->gross_profit = round($totalSalesPrice - $totalPurchasePrice, 2);
        $order->net_profit = round(((float) ($order->total ?? 0)) - $totalPurchasePrice, 2);
        $this->setOrderFlag($order, 'is_stock_deducted', 1);
        $this->appendOrderTrack($order, 'stock_deducted', [
            'total_purchase_price' => $order->total_purchase_price,
            'gross_profit' => $order->gross_profit,
            'net_profit' => $order->net_profit,
        ]);
        $order->save();
        app(\App\Services\Commission\OrderCommissionService::class)->calculateForOrder($order->fresh());
    }

    /**
     * Reverse stock, unit and accounting side effects when an ecommerce order
     * transitions from an approved state back into a cancel_state.
     *
     * Mirrors DesktopPosController::rollbackPosOrderEffects but also respects
     * per-line warehouse assignment where available.
     */
    protected function rollbackEcomOrderEffects(ProductOrder $order): void
    {
        if ((int) ($order->is_stock_deducted ?? 0) !== 1) {
            app(\App\Services\Commission\OrderCommissionService::class)->reverseForOrder($order);
            return;
        }

        app(\App\Services\Commission\OrderCommissionService::class)->reverseForOrder($order);

        $order->loadMissing('order_products');

        // Reset purchase units linked to this order
        ProductPurchaseOrderProductUnit::where('sale_id', $order->id)
            ->update([
                'sale_id'                => null,
                'unit_status'            => 'instock',
                'product_order_product_id' => null,
                'updated_at'             => Carbon::now(),
            ]);

        foreach ($order->order_products as $item) {
            $qty = (float) ($item->qty ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $product = Product::find($item->product_id);
            if ($product) {
                $product->increment('stock', $qty);
            }

            if ($item->variant_id) {
                $variant = ProductVariantCombination::find($item->variant_id);
                if ($variant) {
                    $variant->increment('stock', $qty);
                }
            }

            $warehouseId = $item->product_warehouse_id ?: $order->product_warehouse_id;

            $this->incrementEcomProductStock(
                $item->product_id,
                $item->variant_id ?: null,
                $warehouseId,
                $item->product_warehouse_room_id ?: null,
                $item->product_warehouse_room_cartoon_id ?: null,
                $qty
            );

            $item->product_purchase_order_id = null;
            $item->product_purchase_order_product_id = null;
            $item->product_purchase_order_product_unit_id = null;
            $item->unit_price_id = null;
            $item->purchase_price = null;
            $item->unit_profit = 0;
            $item->net_profit = 0;
            $item->cost_method = null;
            $item->item_meta = array_merge((array) ($item->item_meta ?? []), [
                'stock_allocations_reversed_at' => Carbon::now()->toDateTimeString(),
            ]);
            $item->save();
        }

        // Remove stock movement logs; ecommerce accounting is reversed with audit rows.
        ProductStockLog::where('product_sales_id', $order->id)
            ->where('type', 'sales')
            ->delete();

        $order->total_purchase_price = 0;
        $order->gross_profit = 0;
        $order->net_profit = 0;
        $this->setOrderFlag($order, 'is_stock_deducted', 0);
        $this->appendOrderTrack($order, 'stock_rolled_back');
        $order->save();

        if ($order->customer_id) {
            calc_customer_balance($order->customer_id);
        }
    }

    protected function assertEcomStockAvailable(int $productId, ?int $variantId, int $warehouseId, ?int $roomId, ?int $cartonId, float $qty): void
    {
        $available = (clone $this->ecomProductStockQuery($productId, $variantId, $warehouseId, $roomId, $cartonId))->sum('qty');
        if ($available < $qty) {
            throw new \RuntimeException("Insufficient warehouse stock for product ID {$productId}. Available: {$available}, required: {$qty}");
        }
    }

    protected function decrementEcomProductStock(int $productId, ?int $variantId, int $warehouseId, ?int $roomId, ?int $cartonId, float $qty): void
    {
        $remaining = $qty;
        $rows = $this->ecomProductStockQuery($productId, $variantId, $warehouseId, $roomId, $cartonId)
            ->where('qty', '>', 0)
            ->lockForUpdate()
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((float) $row->qty, $remaining);
            ProductStock::whereKey($row->id)->decrement('qty', $take);
            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \RuntimeException("Unable to deduct full stock for product ID {$productId}");
        }
    }

    protected function incrementEcomProductStock(int $productId, ?int $variantId, ?int $warehouseId, ?int $roomId, ?int $cartonId, float $qty): void
    {
        $row = $this->ecomProductStockQuery($productId, $variantId, $warehouseId, $roomId, $cartonId)
            ->lockForUpdate()
            ->orderBy('id')
            ->first();

        if ($row) {
            ProductStock::whereKey($row->id)->increment('qty', $qty);
        }
    }

    protected function ecomProductStockQuery(int $productId, ?int $variantId, ?int $warehouseId, ?int $roomId, ?int $cartonId)
    {
        $query = ProductStock::where('product_id', $productId)
            ->where('status', 'active');

        if ($warehouseId) {
            $query->where('product_warehouse_id', $warehouseId);
        }
        if ($roomId) {
            $query->where('product_warehouse_room_id', $roomId);
        }
        if ($cartonId) {
            $query->where('product_warehouse_room_cartoon_id', $cartonId);
        }
        if ($variantId) {
            $query->where('variant_combination_id', $variantId);
        } else {
            $query->where(function ($q) {
                $q->whereNull('variant_combination_id')
                    ->orWhere('has_variant', 0);
            });
        }

        return $query;
    }

    protected function isEcommerceAccountingReady(ProductOrder $order): bool
    {
        $deliveryInfo = is_array($order->delivery_info)
            ? $order->delivery_info
            : (json_decode($order->delivery_info, true) ?: []);

        $method = strtolower((string) ($deliveryInfo['delivery_method'] ?? 'home'));
        if (in_array($order->order_status, [
            'invoiced', 
            'delivered'
        ], true)) {
            return true;
        }

        if ($method === 'store') {
            return in_array($order->order_status, ['delivered', 'picked_up'], true);
        }

        return (int) ($order->is_couriered ?? 0) === 1 || !empty($deliveryInfo['courier_method']);
    }

    protected function setOrderFlag(ProductOrder $order, string $column, int $value): void
    {
        if (Schema::hasColumn($order->getTable(), $column)) {
            $order->{$column} = $value;
        }
    }

    protected function appendOrderTrack(ProductOrder $order, string $event, array $data = []): void
    {
        $tracks = is_array($order->order_tracks)
            ? $order->order_tracks
            : (json_decode($order->order_tracks, true) ?: []);

        $tracks[] = array_merge([
            'event' => $event,
            'at' => Carbon::now()->toDateTimeString(),
            'user_id' => Auth::id(),
        ], $data);

        $order->order_tracks = $tracks;
    }

    /**
     * Trigger courier order creation for ecommerce orders, mirroring the logic
     * from DesktopPosController but only when the order has not been couriered yet.
     */
    protected function maybeCreateCourierOrder(ProductOrder $order): void
    {
        $deliveryInfo = is_array($order->delivery_info)
            ? $order->delivery_info
            : (json_decode($order->delivery_info, true) ?: []);

        if (!empty($deliveryInfo['courier_method']) && (int) ($order->is_couriered ?? 0) === 0) {
            $methodTitle = strtolower($deliveryInfo['courier_method'] ?? '');
            $methodId    = $deliveryInfo['courier_method_id'];

            if ($methodTitle === 'pathao') {
                app(PathaoController::class)->createOrder($order, $methodId);
                Log::info('Pathao order created for order: line '. __FILE__ . ' ' . __LINE__ . ' order_id: ' . $order->id);
            } elseif ($methodTitle === 'steadfast') {
                app(SteadfastController::class)->createOrder($order, $methodId);
                Log::info('Steadfast order created for order: line '. __FILE__ . ' ' . __LINE__ . ' order_id: ' . $order->id);
            }
        }else{
            Log::info('No courier method found for order: line '. __FILE__ . ' ' . __LINE__ . ' order_id: ' . $order->id);
        }

    }
}
