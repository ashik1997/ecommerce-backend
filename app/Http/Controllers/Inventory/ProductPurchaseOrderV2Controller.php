<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Inventory\Models\ProductSupplier;
use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use App\Http\Controllers\Inventory\Models\ProductWarehouseRoom;
use App\Http\Controllers\Inventory\Models\ProductWarehouseRoomCartoon;
use App\Http\Controllers\Inventory\Models\ProductPurchaseOrder;
use App\Http\Controllers\Inventory\Models\ProductPurchaseOrderProduct;
use App\Http\Controllers\Inventory\Models\ProductPurchaseOtherCharge;
use App\Http\Controllers\Inventory\Models\ProductStock;
use App\Mail\PurchaseInvoiceEmail;
use App\Models\GeneralInfo;
use App\Models\Product;
use App\Models\ProductPurchaseOrderProductUnit;
use App\Models\ProductVariantCombination;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Brian2694\Toastr\Facades\Toastr;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ProductPurchaseOrderV2Controller extends Controller
{
    private function consumedPurchaseUnitStatuses(): array
    {
        return ['sold', 'returned', 'lost', 'damaged'];
    }

    private function consumedPurchaseUnitCount($purchaseOrderId): int
    {
        return ProductPurchaseOrderProductUnit::where('product_purchase_order_id', $purchaseOrderId)
            ->whereIn('unit_status', $this->consumedPurchaseUnitStatuses())
            ->count();
    }

    private function validatePurchaseOrderPayload(Request $request, $ignorePurchaseOrderId = null): array
    {
        $errors = [];
        $products = $request->input('product', []);

        if (!is_array($products) || count($products) === 0) {
            return ['Please add at least one product to the purchase order.'];
        }

        $validProductRows = 0;
        $submittedBarcodeProducts = [];

        foreach ($products as $index => $productItem) {
            $rowNo = $index + 1;
            $productId = $productItem['id'] ?? $productItem['product_id'] ?? null;
            if (!$productId) {
                continue;
            }

            $validProductRows++;
            $quantity = $productItem['quantities'] ?? $productItem['quantity'] ?? null;
            $price = $productItem['prices'] ?? $productItem['price'] ?? null;

            if (!is_numeric($quantity) || (float)$quantity < 1) {
                $errors[] = "Row {$rowNo}: quantity must be at least 1.";
            }

            if (!is_numeric($price) || (float)$price <= 0) {
                $errors[] = "Row {$rowNo}: unit price must be greater than 0.";
            }

            $unitDetailsRaw = $productItem['unit_details'] ?? '[]';
            $unitDetails = is_array($unitDetailsRaw)
                ? $unitDetailsRaw
                : (json_decode($unitDetailsRaw, true) ?? []);

            $barcodesRaw = $productItem['barcodes'] ?? '[]';
            $barcodes = is_array($barcodesRaw)
                ? $barcodesRaw
                : (json_decode($barcodesRaw, true) ?? []);

            $qtyInt = max(0, (int)$quantity);
            for ($i = 0; $i < $qtyInt; $i++) {
                $unitDetail = is_array($unitDetails[$i] ?? null) ? $unitDetails[$i] : [];
                $barcode = trim((string)($unitDetail['barcode'] ?? $barcodes[$i] ?? ''));
                if ($barcode !== '') {
                    $submittedBarcodeProducts[$barcode][(string)$productId] = (int)$productId;
                }
            }
        }

        if ($validProductRows === 0) {
            $errors[] = 'Please add at least one valid product to the purchase order.';
        }

        foreach ($submittedBarcodeProducts as $barcode => $productIds) {
            if (count($productIds) > 1) {
                $errors[] = "Barcode is assigned to multiple products in this purchase: {$barcode}.";
            }
        }

        if (!empty($submittedBarcodeProducts)) {
            $existingBarcodeQuery = ProductPurchaseOrderProductUnit::whereIn('code', array_keys($submittedBarcodeProducts))
                ->select('code', 'product_id')
                ->distinct();
            if ($ignorePurchaseOrderId) {
                $existingBarcodeQuery->where('product_purchase_order_id', '!=', $ignorePurchaseOrderId);
            }

            foreach ($existingBarcodeQuery->get() as $existingUnit) {
                $submittedProductIds = $submittedBarcodeProducts[$existingUnit->code] ?? [];
                if (!array_key_exists((string)$existingUnit->product_id, $submittedProductIds)) {
                    $errors[] = "Barcode already belongs to another product: {$existingUnit->code}.";
                    break;
                }
            }
        }

        return $errors;
    }

    private function purchaseValidationResponse(Request $request, array $errors)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $errors[0] ?? 'Purchase validation failed.',
                'errors' => ['purchase' => $errors],
            ], 422);
        }

        Toastr::error($errors[0] ?? 'Purchase validation failed.', 'Error');
        return back()->withErrors(['purchase' => $errors])->withInput();
    }

    public function addNewPurchaseProductOrder()
    {
        $suppliers            = ProductSupplier::where('status', 'active')->get();
        $productWarehouses    = ProductWarehouse::where('status', 'active')->get();
        $other_charges_types  = ProductPurchaseOtherCharge::where('status', 'active')->get();

        return view('backend.purchase_product_order.createv2', compact(
            'suppliers',
            'productWarehouses',
            'other_charges_types'
        ));
    }

    /* ─────────────────────────────────────────────────────────────
     * SAVE (CREATE)
     * New form sends:
     *   product[N][barcodes]  = JSON-encoded array of barcode strings
     * All other fields stay the same as before.
     * ───────────────────────────────────────────────────────────── */
    public function saveNewPurchaseProductOrder(Request $request)
    {
        // dd(request()->all());
        $validator = Validator::make($request->all(), [
            'purchase_product_warehouse_id' => 'required',
            'supplier_id'                   => 'required',
            'purchase_date'                 => 'required|date',
        ], [
            'purchase_product_warehouse_id.required' => 'Warehouse is required.',
            'supplier_id.required'                   => 'Supplier is required.',
            'purchase_date.required'                 => 'Purchase date is required.',
            'purchase_date.date'                     => 'Purchase date must be a valid date.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $purchaseErrors = $this->validatePurchaseOrderPayload($request);
        if (!empty($purchaseErrors)) {
            return $this->purchaseValidationResponse($request, $purchaseErrors);
        }

        try {
            DB::beginTransaction();

            $other_charge_total = $this->calc_other_charges(
                $request->other_charges,
                $request->subtotal_amt
            );

            $random_no = random_int(100, 999) . random_int(1000, 9999);
            $slug      = Str::orderedUuid() . uniqid() . $random_no;
            $user      = auth()->user();
            $order_status = request()->order_status ?? 'pending';

            /* ── Create order header ── */
            $order = new ProductPurchaseOrder();
            $order->product_warehouse_id              = $request->purchase_product_warehouse_id;
            $order->product_warehouse_room_id         = null;
            $order->product_warehouse_room_cartoon_id = null;
            $order->product_supplier_id               = $request->supplier_id;
            $order->date                              = $request->purchase_date;
            $order->other_charge_type                 = ($request->other_charges);
            $order->other_charge_amount               = $other_charge_total;
            $order->discount_type                     = $request->discount_to_all_type ?? 'in_percentage';
            $order->discount_amount                   = $request->discount_on_all ?? 0;
            $order->calculated_discount_amount        = $request->discount_to_all_amt ?? 0;
            $order->round_off                         = (float)($request->total_round_off_amt ?? 0);
            $order->subtotal                          = $request->subtotal_amt;
            $order->total                             = $request->grand_total_amt;
            $order->note                              = $request->purchase_note;
            $order->order_status                      = 'pending';
            $order->creator                           = $user->id;
            $order->status                            = 'active';
            $order->created_at                        = Carbon::now();
            $order->save();

            /* ── Process line items ── */
            $products = $request->input('product', []);
            $unitColumnExists = [
                'serial_no' => Schema::hasColumn('product_purchase_order_product_units', 'serial_no'),
                'imei_1' => Schema::hasColumn('product_purchase_order_product_units', 'imei_1'),
                'imei_2' => Schema::hasColumn('product_purchase_order_product_units', 'imei_2'),
                'supplier_warranty_start_date' => Schema::hasColumn('product_purchase_order_product_units', 'supplier_warranty_start_date'),
                'supplier_warranty_end_date' => Schema::hasColumn('product_purchase_order_product_units', 'supplier_warranty_end_date'),
                'warranty_note' => Schema::hasColumn('product_purchase_order_product_units', 'warranty_note'),
                'extra_attributes' => Schema::hasColumn('product_purchase_order_product_units', 'extra_attributes'),
            ];

            foreach ($products as $productItem) {
                $productId = $productItem['id'] ?? $productItem['product_id'] ?? null;
                if (!$productId) {
                    continue;
                }

                $variantCombinationId = $productItem['variant_combination_id'] ?? null;
                $variantCombinationId = ($variantCombinationId === '' || $variantCombinationId === 'null')
                    ? null
                    : $variantCombinationId;

                $unit_price       = (float)($productItem['prices']     ?? $productItem['price']    ?? 0);
                $discount_percent = (float)($productItem['discounts']   ?? $productItem['discount'] ?? 0);
                $tax_percent      = (float)($productItem['taxes']       ?? $productItem['tax']      ?? 0);
                $quantity         = (float)($productItem['quantities']  ?? $productItem['quantity'] ?? 0);

                $warehouseRoomId    = $productItem['warehouse_room_id'] ?? null;
                $warehouseRoomId    = ($warehouseRoomId    === '' || $warehouseRoomId    === 'null') ? null : $warehouseRoomId;
                $warehouseCartoonId = $productItem['warehouse_cartoon_id'] ?? null;
                $warehouseCartoonId = ($warehouseCartoonId === '' || $warehouseCartoonId === 'null') ? null : $warehouseCartoonId;

                $discounted_price     = $unit_price * (1 - ($discount_percent / 100));
                $final_price_per_unit = $discounted_price * (1 + ($tax_percent / 100));

                $product_slug = Str::orderedUuid() . $random_no . $order->id . uniqid();
                $product      = Product::find($productId);
                $productName  = $productItem['display_name'] ?? $productItem['name'] ?? ($product->name ?? null);

                /* ── Decode barcodes from hidden input ── */
                $barcodesRaw = $productItem['barcodes'] ?? '[]';
                $barcodes    = is_array($barcodesRaw)
                    ? $barcodesRaw
                    : (json_decode($barcodesRaw, true) ?? []);
                // Filter empty strings
                $barcodes = array_values(array_filter($barcodes, fn($b) => trim((string)$b) !== ''));

                $unitDetailsRaw = $productItem['unit_details'] ?? '[]';
                $unitDetails    = is_array($unitDetailsRaw)
                    ? $unitDetailsRaw
                    : (json_decode($unitDetailsRaw, true) ?? []);

                /* ── Build create payload ── */
                $createPayload = [
                    'product_warehouse_id'              => $request->purchase_product_warehouse_id,
                    'product_warehouse_room_id'         => $warehouseRoomId,
                    'product_warehouse_room_cartoon_id' => $warehouseCartoonId,
                    'product_supplier_id'               => $request->supplier_id,
                    'product_purchase_order_id'         => $order->id,
                    'product_id'                        => $productId,
                    'product_name'                      => $productName,
                    'qty'                               => $quantity,
                    'product_price'                     => $unit_price,
                    'discount_type'                     => 'in_percentage',
                    'discount_amount'                   => $discount_percent,
                    'tax'                               => $tax_percent,
                    'purchase_price'                    => $final_price_per_unit,
                    'slug'                              => $product_slug,
                ];

                if (Schema::hasColumn('product_purchase_order_products', 'variant_combination_id')) {
                    $createPayload['variant_combination_id'] = $variantCombinationId;
                }
                if (Schema::hasColumn('product_purchase_order_products', 'previous_stock')) {
                    $createPayload['previous_stock'] = $productItem['previous_stock'] ?? null;
                }
                if (Schema::hasColumn('product_purchase_order_products', 'barcodes')) {
                    $createPayload['barcodes'] = json_encode($barcodes);
                }

                $orderProduct = ProductPurchaseOrderProduct::create($createPayload);

                /* ── Pre-create unit records: one per qty slot ──────────────
                 * If a barcode was submitted for that slot, use it.
                 * Otherwise auto-generate a unique 8-char code.
                 * Units become 'instock' after order confirm.
                 * ─────────────────────────────────────────────────────────── */

                $qty_int = max(1, (int)$quantity);
                for ($ui = 0; $ui < $qty_int; $ui++) {
                    $unitDetail = is_array($unitDetails[$ui] ?? null) ? $unitDetails[$ui] : [];
                    $rawCode = trim((string)($unitDetail['barcode'] ?? $barcodes[$ui] ?? ''));
                    $code    = $rawCode !== ''
                        ? $rawCode
                        : strtoupper(substr(md5(uniqid($productId . $ui, true)), 0, 8));

                    $unit = new ProductPurchaseOrderProductUnit();
                    $unit->product_warehouse_id                   = $request->purchase_product_warehouse_id;
                    $unit->product_purchase_order_id              = $order->id;
                    $unit->product_purchase_order_product_id      = $orderProduct->id;
                    $unit->product_id                             = $productId;
                    $unit->variant_combination_id                 = $variantCombinationId ?? null;
                    $unit->code                                   = $code;
                    $unit->price                                  = $final_price_per_unit;
                    $unit->unit_status                            = 'pending';
                    $unit->creator                                = $user->id;
                    $unit->slug                                   = $productId . $ui . uniqid();
                    $unit->created_at                             = Carbon::now();

                    if ($unitColumnExists['serial_no']) {
                        $unit->serial_no = $unitDetail['serial_no'] ?? null;
                    }
                    if ($unitColumnExists['imei_1']) {
                        $unit->imei_1 = $unitDetail['imei_1'] ?? null;
                    }
                    if ($unitColumnExists['imei_2']) {
                        $unit->imei_2 = $unitDetail['imei_2'] ?? null;
                    }
                    if ($unitColumnExists['supplier_warranty_start_date']) {
                        $unit->supplier_warranty_start_date = $unitDetail['supplier_warranty_start_date'] ?? null;
                    }
                    if ($unitColumnExists['supplier_warranty_end_date']) {
                        $unit->supplier_warranty_end_date = $unitDetail['supplier_warranty_end_date'] ?? null;
                    }
                    if ($unitColumnExists['warranty_note']) {
                        $unit->warranty_note = $unitDetail['warranty_note'] ?? null;
                    }
                    if ($unitColumnExists['extra_attributes']) {
                        $unit->extra_attributes = $unitDetail['extra_attributes'] ?? [];
                    }

                    $unit->save();
                }
            }

            /* ── Generate order code ── */
            $last = ProductPurchaseOrder::whereNotNull('code')->orderBy('id', 'desc')->first();
            $new_code = $last && $last->code
                ? 'PO' . ((int) preg_replace('/[^0-9]/', '', $last->code) + 1)
                : 'PO10001';

            $order->code      = $new_code;
            $order->reference = $request->reference ?? null;
            $order->slug      = $order->id . $slug;
            $order->save();

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                $redirect = $request->order_status === 'received'
                    ? url('edit/purchase-product/order/confirm') . '/' . $order->slug
                    : back()->getTargetUrl() ?? url()->previous() ?? url('view/all/purchase-product/order');
                return response()->json([
                    'success' => true,
                    'message' => 'Purchase Order created successfully!',
                    'redirect' => $redirect,
                ]);
            }

            if ($request->order_status === 'received') {
                return redirect(url('edit/purchase-product/order/confirm') . '/' . $order->slug);
            }

            Toastr::success('Purchase Order created successfully!', 'Success');
            return back();

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong! ' . $e->getMessage(),
                ], 500);
            }

            Toastr::error('Something went wrong! ' . $e->getMessage(), 'Error');
            return back()->withInput();
        }
    }

    /* ─────────────────────────────────────────────────────────────
     * UPDATE
     * Mirrors save but for existing orders. Barcodes decoded same way.
     * ───────────────────────────────────────────────────────────── */
    public function updatePurchaseProductOrder(Request $request)
    {
        $message = 'The old purchase update form is disabled. Please use the V2 purchase edit form.';
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], 410);
        }

        Toastr::error($message, 'Error');
        return redirect()->route('ViewAllPurchaseProductOrder');

        $other_charge_total = $this->calc_other_charges(
            $request->other_charges,
            $request->subtotal_amt ?? $request->subtotal
        );

        $order = ProductPurchaseOrder::where('id', $request->purchase_product_order_id)->firstOrFail();
        $user  = auth()->user();

        $order->product_warehouse_id              = $request->purchase_product_warehouse_id;
        $order->product_warehouse_room_id         = null;
        $order->product_warehouse_room_cartoon_id = null;
        $order->product_supplier_id               = $request->supplier_id;
        $order->date                              = $request->purchase_date;
        $order->other_charge_type                 = json_encode($request->other_charges);
        $order->other_charge_amount               = $other_charge_total;
        $order->discount_type                     = $request->discount_to_all_type ?? 'in_percentage';
        $order->discount_amount                   = $request->discount_on_all ?? 0;
        $order->calculated_discount_amount        = $request->discount_to_all_amt ?? 0;
        $order->round_off                         = (float)($request->total_round_off_amt ?? 0);
        $order->subtotal                          = $request->subtotal_amt ?? $request->subtotal;
        $order->total                             = $request->grand_total_amt;
        $order->note                              = $request->purchase_note;
        $order->creator                           = $user->id;
        $order->status                            = 'active';
        $order->updated_at                        = Carbon::now();
        $order->save();

        $variantColumnExists       = Schema::hasColumn('product_purchase_order_products', 'variant_combination_id');
        $previousStockColumnExists = Schema::hasColumn('product_purchase_order_products', 'previous_stock');
        $barcodesColumnExists      = Schema::hasColumn('product_purchase_order_products', 'barcodes');

        $processedIds = [];
        $products     = $request->input('product', []);

        foreach ($products as $productItem) {
            $product_id = $productItem['id'] ?? $productItem['product_id'] ?? null;
            if (!$product_id) continue;

            $variantCombinationId = $productItem['variant_combination_id'] ?? null;
            $variantCombinationId = ($variantCombinationId === '' || $variantCombinationId === 'null')
                ? null : $variantCombinationId;

            $unit_price       = (float)($productItem['prices']    ?? $productItem['price']    ?? 0);
            $discount_percent = (float)($productItem['discounts'] ?? $productItem['discount'] ?? 0);
            $tax_percent      = (float)($productItem['taxes']     ?? $productItem['tax']      ?? 0);
            $quantity         = (float)($productItem['quantities'] ?? $productItem['quantity'] ?? 0);

            $warehouseRoomId    = $productItem['warehouse_room_id'] ?? null;
            $warehouseRoomId    = ($warehouseRoomId    === '' || $warehouseRoomId    === 'null') ? null : $warehouseRoomId;
            $warehouseCartoonId = $productItem['warehouse_cartoon_id'] ?? null;
            $warehouseCartoonId = ($warehouseCartoonId === '' || $warehouseCartoonId === 'null') ? null : $warehouseCartoonId;

            $discounted_price     = $unit_price * (1 - ($discount_percent / 100));
            $final_price_per_unit = $discounted_price * (1 + ($tax_percent / 100));
            $product_slug         = Str::orderedUuid() . $order->id . uniqid();
            $productName          = $productItem['display_name'] ?? $productItem['name'] ?? null;

            $barcodesRaw = $productItem['barcodes'] ?? '[]';
            $barcodes    = is_array($barcodesRaw)
                ? $barcodesRaw
                : (json_decode($barcodesRaw, true) ?? []);
            $barcodes = array_values(array_filter($barcodes, fn($b) => trim((string)$b) !== ''));

            $query = ProductPurchaseOrderProduct::where('product_purchase_order_id', $order->id)
                ->where('product_id', $product_id);
            if ($variantColumnExists) {
                $query->when($variantCombinationId,
                    fn($q) => $q->where('variant_combination_id', $variantCombinationId),
                    fn($q) => $q->whereNull('variant_combination_id')
                );
            }
            $existingProduct = $query->first();

            $payload = [
                'product_warehouse_id'              => $request->purchase_product_warehouse_id,
                'product_warehouse_room_id'         => $warehouseRoomId,
                'product_warehouse_room_cartoon_id' => $warehouseCartoonId,
                'product_supplier_id'               => $request->supplier_id,
                'product_name'                      => $productName,
                'qty'                               => $quantity,
                'product_price'                     => $unit_price,
                'discount_type'                     => 'in_percentage',
                'discount_amount'                   => $discount_percent,
                'tax'                               => $tax_percent,
                'purchase_price'                    => $final_price_per_unit,
                'slug'                              => $product_slug,
                'updated_at'                        => now(),
            ];

            if ($previousStockColumnExists) $payload['previous_stock'] = $productItem['previous_stock'] ?? null;
            if ($variantColumnExists)        $payload['variant_combination_id'] = $variantCombinationId;
            if ($barcodesColumnExists)       $payload['barcodes'] = json_encode($barcodes);

            if ($existingProduct) {
                $existingProduct->update($payload);
                $processedIds[] = $existingProduct->id;
            } else {
                $payload['product_purchase_order_id'] = $order->id;
                $payload['product_id']  = $product_id;
                $payload['created_at']  = now();
                $newProduct = ProductPurchaseOrderProduct::create($payload);
                $processedIds[] = $newProduct->id;
            }
        }

        // Remove items that were deleted from the form
        if (!empty($processedIds)) {
            ProductPurchaseOrderProduct::where('product_purchase_order_id', $order->id)
                ->whereNotIn('id', $processedIds)->delete();
        } else {
            ProductPurchaseOrderProduct::where('product_purchase_order_id', $order->id)->delete();
        }

        Toastr::success('Purchase Order updated successfully!', 'Success');
        return redirect()->route('ViewAllPurchaseProductOrder');
    }

    /* ─────────────────────────────────────────────────────────────
     * OTHER CHARGES HELPER
     * ───────────────────────────────────────────────────────────── */
    public function calc_other_charges($other_charges, $subtotal)
    {
        $percent_total = 0;
        $fixed_total   = 0;
        $other_charges = is_array($other_charges) ? $other_charges : [];

        foreach ($other_charges as $charge) {
            if (!is_array($charge)) continue;
            $amount = (float)($charge['amount'] ?? 0);
            if ($amount <= 0) continue;
            if (isset($charge['type']) && $charge['type'] === 'percent') {
                $percent_total += ($subtotal * $amount) / 100;
            } else {
                $fixed_total += $amount;
            }
        }
        return $percent_total + $fixed_total;
    }

    /* ─────────────────────────────────────────────────────────────
     * ALL REMAINING METHODS (unchanged from original)
     * ───────────────────────────────────────────────────────────── */

    public function viewAllPurchaseProductOrder(Request $request)
    {
        if ($request->ajax()) {
            $data = ProductPurchaseOrder::with([
                'creator',
                'order_products.product',
                'order_products.variantCombination',
            ])
            ->when($request->filled('order_id'), function ($query) use ($request) {
                $query->where('id', $request->order_id);
            })
            ->orderBy('id', 'desc');
            return DataTables::of($data)
                ->editColumn('status', fn($d) => $d->status == 'active' ? 'Active' : 'Inactive')
                ->editColumn('created_at', fn($d) => date('Y-m-d', strtotime($d->created_at)))
                ->editColumn('total', function ($d) {
                    $total_products = $this->uniquePurchaseProducts($d)->count();
                    return "<div class='po-total-box'>
                        <div>Unique Products <span class='badge badge-info'>{$total_products}</span></div>
                        <div>Total <span class='badge badge-info'>" . number_format((float)$d->total, 2) . "</span></div>
                    </div>";
                })
                ->addIndexColumn()
                ->addColumn('product', function ($d) {
                    $products = $this->uniquePurchaseProducts($d);

                    if ($products->isEmpty()) {
                        return '<span class="text-muted">No products found</span>';
                    }

                    $items = $products->map(function ($item) {
                        $variants = $item['variants']->isNotEmpty()
                            ? '<div class="po-product-variants">' . e($item['variants']->implode(', ')) . '</div>'
                            : '';

                        return '<div class="po-product-item">
                            <img class="po-product-img gridProductImage" src="' . e($item['image']) . '" alt="' . e($item['name']) . '"
                                onerror="this.src=\'' . asset('assets/images/default-product.png') . '\'">
                            ' . $variants . '
                            <div class="po-product-meta">Qty: ' . e($item['qty']) . '</div>
                        </div>';
                    })->implode('');

                    return '<div class="po-product-cell">
                        <div class="po-product-list">' . $items . '</div>
                        <button type="button" class="btn btn-sm btn-outline-info po-details-btn js-po-details"
                            data-slug="' . e($d->slug) . '" data-code="' . e($d->code) . '">
                            <i class="fas fa-eye"></i> Details
                        </button>
                    </div>';
                })
                ->addColumn('action', function ($d) {
                    $invoiceUrl  = url('purchase-invoice') . '/' . $d->slug;
                    $editV2Url   = url('edit/purchase-product/order/v2') . '/' . $d->slug;
                    $confirmUrl  = url('edit/purchase-product/order/confirm') . '/' . $d->slug;
                    $barcodeUrl  = url('print-purchase-barcode') . '/' . $d->id;
                    $isAdmin     = ((int)(auth()->user()->user_type ?? 0) === 1);

                    if ($d->order_status !== 'received') {
                        return '<div class="dropdown">
                            <button class="btn-sm btn-primary dropdown-toggle rounded" type="button" data-toggle="dropdown">Action</button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="' . $editV2Url . '"><i class="fas fa-edit"></i> Edit</a>
                                <a class="dropdown-item" href="' . $confirmUrl . '"><i class="fas fa-check"></i> Confirm Order</a>
                                <a class="dropdown-item" href="' . $invoiceUrl . '"><i class="fas fa-file-invoice"></i> View Invoice</a>
                                <a class="dropdown-item deleteBtn" href="javascript:void(0)" data-id="' . $d->slug . '"><i class="fas fa-trash-alt"></i> Delete</a>
                            </div>
                        </div>';
                    }

                    $adminEdit = $isAdmin
                        ? '<a class="dropdown-item" href="' . $editV2Url . '"><i class="fas fa-edit text-warning"></i> Edit (Admin)</a>'
                        : '';

                    return '<div class="dropdown">
                        <button class="btn-sm btn-success dropdown-toggle rounded" type="button" data-toggle="dropdown">Action</button>
                        <div class="dropdown-menu">
                            ' . $adminEdit . '
                            <a class="dropdown-item" href="' . $invoiceUrl . '"><i class="fas fa-file-invoice"></i> View Invoice</a>
                            <a class="dropdown-item" href="' . $barcodeUrl . '"><i class="fas fa-barcode"></i> Generate Barcode</a>
                            <a class="dropdown-item deleteBtn" href="javascript:void(0)" data-id="' . $d->slug . '"><i class="fas fa-trash-alt"></i> Delete</a>
                        </div>
                    </div>';
                })
                ->filterColumn('product', function ($query, $keyword) {
                    $query->whereHas('order_products', function ($productQuery) use ($keyword) {
                        $productQuery->where('product_name', 'like', "%{$keyword}%")
                            ->orWhereHas('product', function ($q) use ($keyword) {
                                $q->where('name', 'like', "%{$keyword}%");
                            });
                    });
                })
                ->rawColumns(['action', 'total','product'])
                ->make(true);
        }
        return view('backend.purchase_product_order.view');
    }

    private function uniquePurchaseProducts(ProductPurchaseOrder $order)
    {
        return collect($order->order_products)
            ->groupBy(fn($item) => $item->product_id ?: mb_strtolower(trim($item->product_name ?? '')))
            ->map(function ($items) {
                $first = $items->first();
                $product = $first->product;
                $imagePath = optional($items->firstWhere('variantCombination.image', '!=', null)?->variantCombination)->image
                    ?: optional($product)->image;

                return [
                    'name' => $product->name ?? $first->product_name ?? 'Unknown Product',
                    'image' => $this->purchaseProductImageUrl($imagePath),
                    'qty' => $items->sum(fn($item) => (float)($item->qty ?? 0)),
                    'variants' => $items
                        ->map(fn($item) => $this->purchaseVariantLabel($item))
                        ->filter()
                        ->unique()
                        ->values(),
                ];
            })
            ->values();
    }

    private function purchaseProductImageUrl($imagePath)
    {
        if (!$imagePath) {
            return asset('assets/images/default-product.png');
        }

        $imagePath = ltrim($imagePath, '/');
        if (!str_contains($imagePath, '/')) {
            $imagePath = 'productImages/' . $imagePath;
        }

        return get_file_url() . '/' . $imagePath;
    }

    private function purchaseVariantLabel($item)
    {
        $variant = $item->variantCombination;
        if (!$variant) {
            return null;
        }

        $values = $variant->variant_values ?? [];
        if (is_array($values) && count($values)) {
            return collect($values)
                ->map(fn($value, $key) => ucfirst(str_replace('_', ' ', $key)) . ': ' . $value)
                ->implode(' | ');
        }

        return $variant->combination_key ?: $variant->name;
    }

    public function purchaseInvoiceModal($slug)
    {
        $order = ProductPurchaseOrder::with([
            'supplier',
            'warehouse',
            'creator',
            'order_products.product',
            'order_products.variantCombination',
            'order_products.allUnits',
        ])->where('slug', $slug)->firstOrFail();

        $generalInfo = GeneralInfo::where('id', 1)->first() ?? new GeneralInfo();
        $html = view('invoice.purchase.purchase_order_invoice', compact('order', 'generalInfo'))
            ->with('isPublic', false)
            ->render();
        $trackingHtml = view('backend.purchase_product_order.partials.unit_tracking', compact('order'))->render();

        return response()->json([
            'code' => $order->code,
            'html' => $html,
            'tracking_html' => $trackingHtml,
        ]);
    }

    public function editPurchaseProductOrder($slug)
    {
        return redirect()->route('EditPurchaseProductOrderV2', $slug);
    }

    /* ─────────────────────────────────────────────────────────────
     * EDIT V2 — load the modern edit form
     * Admin can edit at any time; non-admin only if still pending.
     * ───────────────────────────────────────────────────────────── */
    public function editPurchaseProductOrderV2($slug)
    {
        $data    = ProductPurchaseOrder::with(['order_products' => fn($q) => $q->with(['product', 'variantCombination'])])
                    ->where('slug', $slug)->firstOrFail();
        $user    = auth()->user();
        $isAdmin = ((int)$user->user_type === 1);

        if ($data->order_status === 'received' && !$isAdmin) {
            Toastr::error('Only admin can edit a confirmed purchase order.', 'Error');
            return redirect()->route('ViewAllPurchaseProductOrder');
        }

        if ($this->consumedPurchaseUnitCount($data->id) > 0) {
            Toastr::error('This purchase order has sold/returned/lost/damaged barcode units and cannot be edited.', 'Error');
            return redirect()->route('ViewAllPurchaseProductOrder');
        }

        $suppliers           = ProductSupplier::where('status', 'active')->get();
        $productWarehouses   = ProductWarehouse::where('status', 'active')->get();
        $other_charges_types = ProductPurchaseOtherCharge::where('status', 'active')->get();

        return view('backend.purchase_product_order.editv2', compact(
            'data', 'suppliers', 'productWarehouses', 'other_charges_types', 'isAdmin'
        ));
    }

    /* ─────────────────────────────────────────────────────────────
     * UPDATE V2 — full rewind + re-apply
     * ───────────────────────────────────────────────────────────── */
    public function updatePurchaseProductOrderV2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_product_order_id'     => 'required|exists:product_purchase_orders,id',
            'purchase_product_warehouse_id' => 'required',
            'supplier_id'                   => 'required',
            'purchase_date'                 => 'required|date',
        ], [
            'purchase_product_order_id.required'     => 'Order ID is missing.',
            'purchase_product_warehouse_id.required' => 'Warehouse is required.',
            'supplier_id.required'                   => 'Supplier is required.',
            'purchase_date.required'                 => 'Purchase date is required.',
            'purchase_date.date'                     => 'Purchase date must be a valid date.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = ProductPurchaseOrder::where('id', $request->purchase_product_order_id)->firstOrFail();
        $user  = auth()->user();

        $purchaseErrors = $this->validatePurchaseOrderPayload($request, $order->id);
        if (!empty($purchaseErrors)) {
            return $this->purchaseValidationResponse($request, $purchaseErrors);
        }

        if ($order->order_status === 'received' && (int)$user->user_type !== 1) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Only admin can edit a confirmed purchase order.'], 403);
            }
            Toastr::error('Only admin can edit a confirmed purchase order.', 'Error');
            return back();
        }

        if ($this->consumedPurchaseUnitCount($order->id) > 0) {
            $message = 'This purchase order has sold/returned/lost/damaged barcode units and cannot be edited.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 409);
            }

            Toastr::error($message, 'Error');
            return back();
        }

        try {
            DB::beginTransaction();

            $wasReceived = ($order->order_status === 'received');

            /* ── Phase 1 : REWIND ───────────────────────────────── */
            if ($wasReceived) {
                // 1a. Reverse accounting entries
                AcTransaction::where('ref_purchase_id', $order->id)
                    ->where('transaction_type', 'PURCHASE_CREATE')
                    ->delete();

                // 1b. Reverse stock counts (load fresh products before delete)
                $existingProducts = ProductPurchaseOrderProduct::where('product_purchase_order_id', $order->id)->get();

                foreach ($existingProducts as $op) {
                    $productModel = Product::find($op->product_id);
                    $hasVariant   = !empty($op->variant_combination_id);

                    if ($hasVariant) {
                        $vc = ProductVariantCombination::find($op->variant_combination_id);
                        if ($vc) {
                            $vc->stock = max(0, (float)($vc->stock ?? 0) - (float)$op->qty);
                            $vc->save();
                        }
                        if ($productModel) {
                            $productModel->stock = $productModel->variantCombinations()->sum('stock');
                            $productModel->save();
                        }
                    } else {
                        if ($productModel) {
                            $productModel->stock = max(0, (float)($productModel->stock ?? 0) - (float)$op->qty);
                            $productModel->save();
                        }
                    }
                }

                // 1c. Delete product_stocks rows for this order
                ProductStock::where('product_purchase_order_id', $order->id)->delete();

                // 1d. Delete all units (instock + pending) for this order
                ProductPurchaseOrderProductUnit::where('product_purchase_order_id', $order->id)->delete();

                // Temporarily reset status so re-confirm step below treats it properly
                $order->order_status = 'pending';
            } else {
                // For pending orders just delete pending units
                ProductPurchaseOrderProductUnit::where('product_purchase_order_id', $order->id)
                    ->where(function ($q) {
                        $q->where('unit_status', 'pending')
                          ->orWhereNull('unit_status')
                          ->orWhere('unit_status', '');
                    })->delete();
            }

            /* ── Phase 2 : DELETE OLD ORDER PRODUCTS ───────────── */
            ProductPurchaseOrderProduct::where('product_purchase_order_id', $order->id)->delete();

            /* ── Phase 3 : UPDATE HEADER ────────────────────────── */
            $other_charge_total = $this->calc_other_charges($request->other_charges, $request->subtotal_amt);
            $random_no          = random_int(100, 999) . random_int(1000, 9999);

            $order->product_warehouse_id              = $request->purchase_product_warehouse_id;
            $order->product_warehouse_room_id         = null;
            $order->product_warehouse_room_cartoon_id = null;
            $order->product_supplier_id               = $request->supplier_id;
            $order->date                              = $request->purchase_date;
            $order->other_charge_type                 = $request->other_charges;
            $order->other_charge_amount               = $other_charge_total;
            $order->discount_type                     = $request->discount_to_all_type ?? 'in_percentage';
            $order->discount_amount                   = $request->discount_on_all ?? 0;
            $order->calculated_discount_amount        = $request->discount_to_all_amt ?? 0;
            $order->round_off                         = (float)($request->total_round_off_amt ?? 0);
            $order->subtotal                          = $request->subtotal_amt;
            $order->total                             = $request->grand_total_amt;
            $order->note                              = $request->purchase_note;
            $order->reference                         = $request->reference ?? null;
            $order->creator                           = $user->id;
            $order->status                            = 'active';
            $order->updated_at                        = Carbon::now();
            $order->save();

            /* ── Phase 4 : RE-CREATE PRODUCTS + UNITS ───────────── */
            $variantColumnExists       = Schema::hasColumn('product_purchase_order_products', 'variant_combination_id');
            $previousStockColumnExists = Schema::hasColumn('product_purchase_order_products', 'previous_stock');
            $barcodesColumnExists      = Schema::hasColumn('product_purchase_order_products', 'barcodes');
            $unitColumnExists = [
                'serial_no' => Schema::hasColumn('product_purchase_order_product_units', 'serial_no'),
                'imei_1' => Schema::hasColumn('product_purchase_order_product_units', 'imei_1'),
                'imei_2' => Schema::hasColumn('product_purchase_order_product_units', 'imei_2'),
                'supplier_warranty_start_date' => Schema::hasColumn('product_purchase_order_product_units', 'supplier_warranty_start_date'),
                'supplier_warranty_end_date' => Schema::hasColumn('product_purchase_order_product_units', 'supplier_warranty_end_date'),
                'warranty_note' => Schema::hasColumn('product_purchase_order_product_units', 'warranty_note'),
                'extra_attributes' => Schema::hasColumn('product_purchase_order_product_units', 'extra_attributes'),
            ];

            foreach ($request->input('product', []) as $productItem) {
                $productId = $productItem['id'] ?? $productItem['product_id'] ?? null;
                if (!$productId) continue;

                $variantCombinationId = $productItem['variant_combination_id'] ?? null;
                $variantCombinationId = ($variantCombinationId === '' || $variantCombinationId === 'null')
                    ? null : $variantCombinationId;

                $unit_price       = (float)($productItem['prices']    ?? $productItem['price']    ?? 0);
                $discount_percent = (float)($productItem['discounts'] ?? $productItem['discount'] ?? 0);
                $tax_percent      = (float)($productItem['taxes']     ?? $productItem['tax']      ?? 0);
                $quantity         = (float)($productItem['quantities'] ?? $productItem['quantity'] ?? 0);

                $warehouseRoomId    = $productItem['warehouse_room_id'] ?? null;
                $warehouseRoomId    = ($warehouseRoomId    === '' || $warehouseRoomId    === 'null') ? null : $warehouseRoomId;
                $warehouseCartoonId = $productItem['warehouse_cartoon_id'] ?? null;
                $warehouseCartoonId = ($warehouseCartoonId === '' || $warehouseCartoonId === 'null') ? null : $warehouseCartoonId;

                $discounted_price     = $unit_price * (1 - ($discount_percent / 100));
                $final_price_per_unit = $discounted_price * (1 + ($tax_percent / 100));

                $product_slug = Str::orderedUuid() . $random_no . $order->id . uniqid();
                $product      = Product::find($productId);
                // $productName  = $productItem['display_name'] ?? $productItem['name'] ?? ($product->name ?? null);
                $productName  = $productItem['name'] ?? ($product->name ?? null);

                $barcodesRaw = $productItem['barcodes'] ?? '[]';
                $barcodes    = is_array($barcodesRaw) ? $barcodesRaw : (json_decode($barcodesRaw, true) ?? []);
                $barcodes    = array_values(array_filter($barcodes, fn($b) => trim((string)$b) !== ''));
                $unitDetailsRaw = $productItem['unit_details'] ?? '[]';
                $unitDetails    = is_array($unitDetailsRaw)
                    ? $unitDetailsRaw
                    : (json_decode($unitDetailsRaw, true) ?? []);

                $createPayload = [
                    'product_warehouse_id'              => $request->purchase_product_warehouse_id,
                    'product_warehouse_room_id'         => $warehouseRoomId,
                    'product_warehouse_room_cartoon_id' => $warehouseCartoonId,
                    'product_supplier_id'               => $request->supplier_id,
                    'product_purchase_order_id'         => $order->id,
                    'product_id'                        => $productId,
                    'product_name'                      => $productName,
                    'qty'                               => $quantity,
                    'product_price'                     => $unit_price,
                    'discount_type'                     => 'in_percentage',
                    'discount_amount'                   => $discount_percent,
                    'tax'                               => $tax_percent,
                    'purchase_price'                    => $final_price_per_unit,
                    'slug'                              => $product_slug,
                ];

                if ($variantColumnExists)       $createPayload['variant_combination_id'] = $variantCombinationId;
                if ($previousStockColumnExists) $createPayload['previous_stock']         = $productItem['previous_stock'] ?? null;
                if ($barcodesColumnExists)      $createPayload['barcodes']               = json_encode($barcodes);

                $orderProduct = ProductPurchaseOrderProduct::create($createPayload);

                /* ── Pre-create unit records: one per qty slot ──────────────
                 * If a barcode was submitted for that slot, use it.
                 * Otherwise auto-generate a unique 8-char code.
                 * ─────────────────────────────────────────────────────────── */
                $qty_int = max(1, (int)$quantity);
                for ($ui = 0; $ui < $qty_int; $ui++) {
                    $unitDetail = is_array($unitDetails[$ui] ?? null) ? $unitDetails[$ui] : [];
                    $rawCode = trim((string)($unitDetail['barcode'] ?? $barcodes[$ui] ?? ''));
                    $code    = $rawCode !== ''
                        ? $rawCode
                        : strtoupper(substr(md5(uniqid($productId . $ui, true)), 0, 8));

                    $unit = new ProductPurchaseOrderProductUnit();
                    $unit->product_warehouse_id                   = $request->purchase_product_warehouse_id;
                    $unit->product_purchase_order_id              = $order->id;
                    $unit->product_purchase_order_product_id      = $orderProduct->id;
                    $unit->product_id                             = $productId;
                    $unit->variant_combination_id                 = $variantCombinationId ?? null;
                    $unit->code                                   = $code;
                    $unit->price                                  = $final_price_per_unit;
                    $unit->unit_status                            = 'pending';
                    $unit->creator                                = $user->id;
                    $unit->slug                                   = $productId . $ui . uniqid();
                    $unit->created_at                             = Carbon::now();
                    if ($unitColumnExists['serial_no']) {
                        $unit->serial_no = $unitDetail['serial_no'] ?? null;
                    }
                    if ($unitColumnExists['imei_1']) {
                        $unit->imei_1 = $unitDetail['imei_1'] ?? null;
                    }
                    if ($unitColumnExists['imei_2']) {
                        $unit->imei_2 = $unitDetail['imei_2'] ?? null;
                    }
                    if ($unitColumnExists['supplier_warranty_start_date']) {
                        $unit->supplier_warranty_start_date = $unitDetail['supplier_warranty_start_date'] ?? null;
                    }
                    if ($unitColumnExists['supplier_warranty_end_date']) {
                        $unit->supplier_warranty_end_date = $unitDetail['supplier_warranty_end_date'] ?? null;
                    }
                    if ($unitColumnExists['warranty_note']) {
                        $unit->warranty_note = $unitDetail['warranty_note'] ?? null;
                    }
                    if ($unitColumnExists['extra_attributes']) {
                        $unit->extra_attributes = $unitDetail['extra_attributes'] ?? [];
                    }
                    $unit->save();
                }
            }

            /* ── Phase 5 : RE-CONFIRM if order was already received ── */
            if ($wasReceived) {
                $order->refresh();
                $order->order_status = 'received';
                $order->save();

                record_purchase_create_accounting($order);

                $newSlug = Str::orderedUuid() . uniqid() . random_int(100, 999);

                $stockHasColumns = [
                    'has_variant'            => Schema::hasColumn('product_stocks', 'has_variant'),
                    'variant_combination_key'=> Schema::hasColumn('product_stocks', 'variant_combination_key'),
                    'variant_sku'            => Schema::hasColumn('product_stocks', 'variant_sku'),
                    'variant_barcode'        => Schema::hasColumn('product_stocks', 'variant_barcode'),
                    'variant_data'           => Schema::hasColumn('product_stocks', 'variant_data'),
                    'variant_price'          => Schema::hasColumn('product_stocks', 'variant_price'),
                    'variant_discount_price' => Schema::hasColumn('product_stocks', 'variant_discount_price'),
                ];
                $logHasColumns = [
                    'has_variant'             => Schema::hasColumn('product_stock_logs', 'has_variant'),
                    'variant_combination_key' => Schema::hasColumn('product_stock_logs', 'variant_combination_key'),
                    'variant_sku'             => Schema::hasColumn('product_stock_logs', 'variant_sku'),
                    'variant_data'            => Schema::hasColumn('product_stock_logs', 'variant_data'),
                    'variant_combination_id'  => Schema::hasColumn('product_stock_logs', 'variant_combination_id'),
                ];

                foreach ($order->order_products as $product) {
                    $product_stock = new ProductStock();
                    $product_stock->product_warehouse_id              = $product->product_warehouse_id;
                    $product_stock->product_warehouse_room_id         = $product->product_warehouse_room_id;
                    $product_stock->product_warehouse_room_cartoon_id = $product->product_warehouse_room_cartoon_id;
                    $product_stock->product_supplier_id               = $product->product_supplier_id;
                    $product_stock->product_purchase_order_id         = $product->product_purchase_order_id;
                    $product_stock->product_id                        = $product->product_id;

                    if (Schema::hasColumn('product_stocks', 'variant_combination_id')) {
                        $product_stock->variant_combination_id = $product->variant_combination_id ?? null;
                    }

                    $variantCombination = null;
                    $hasVariant = !empty($product->variant_combination_id);
                    if ($hasVariant) $variantCombination = ProductVariantCombination::find($product->variant_combination_id);

                    if ($stockHasColumns['has_variant'])             $product_stock->has_variant             = $hasVariant;
                    if ($stockHasColumns['variant_combination_key']) $product_stock->variant_combination_key = $variantCombination->combination_key ?? null;
                    if ($stockHasColumns['variant_sku'])             $product_stock->variant_sku             = $variantCombination->sku ?? null;
                    if ($stockHasColumns['variant_barcode'])         $product_stock->variant_barcode         = $variantCombination->barcode ?? null;
                    if ($stockHasColumns['variant_data'])            $product_stock->variant_data            = $variantCombination?->variant_values;
                    if ($stockHasColumns['variant_price'])           $product_stock->variant_price           = $variantCombination->price ?? null;
                    if ($stockHasColumns['variant_discount_price'])  $product_stock->variant_discount_price  = $variantCombination->discount_price ?? null;

                    $product_stock->date           = $order->date;
                    $product_stock->qty            = $product->qty;
                    $product_stock->purchase_price = $product->purchase_price;
                    $product_stock->status         = 'active';
                    $product_stock->slug           = $newSlug;
                    $product_stock->save();

                    $productModel = Product::find($product->product_id);

                    if ($hasVariant) {
                        if ($variantCombination) {
                            $variantCombination->stock = ($variantCombination->stock ?? 0) + $product_stock->qty;
                            if ($product->product_warehouse_id)              $variantCombination->product_warehouse_id              = $product->product_warehouse_id;
                            if ($product->product_warehouse_room_id)         $variantCombination->product_warehouse_room_id         = $product->product_warehouse_room_id;
                            if ($product->product_warehouse_room_cartoon_id) $variantCombination->product_warehouse_room_cartoon_id = $product->product_warehouse_room_cartoon_id;
                            $variantCombination->save();
                        }
                        if ($productModel) {
                            $productModel->stock = $productModel->variantCombinations()->sum('stock');
                            $productModel->save();
                        }
                    } else {
                        if ($productModel) {
                            $productModel->stock = ($productModel->stock ?? 0) + $product_stock->qty;
                            $productModel->save();
                        }
                    }

                    $logData = [
                        'warehouse_id'        => $product->product_warehouse_id,
                        'product_id'          => $product->product_id,
                        'product_name'        => $productModel->name ?? null,
                        'product_purchase_id' => $order->id,
                        'quantity'            => $product->qty,
                        'type'                => 'purchase',
                    ];
                    if ($logHasColumns['has_variant'])             $logData['has_variant']             = $hasVariant;
                    if ($logHasColumns['variant_combination_key']) $logData['variant_combination_key'] = $variantCombination->combination_key ?? null;
                    if ($logHasColumns['variant_sku'])             $logData['variant_sku']             = $variantCombination->sku ?? null;
                    if ($logHasColumns['variant_data'])            $logData['variant_data']            = $variantCombination?->variant_values ?? null;
                    if ($logHasColumns['variant_combination_id'])  $logData['variant_combination_id']  = $product->variant_combination_id ?? null;

                    insert_stock_log($logData);

                    /* ── Activate pending units ── */
                    $pendingCount = ProductPurchaseOrderProductUnit::where('product_purchase_order_product_id', $product->id)
                        ->where(function ($q) {
                            $q->where('unit_status', 'pending')->orWhereNull('unit_status')->orWhere('unit_status', '');
                        })->count();

                    if ($pendingCount > 0) {
                        ProductPurchaseOrderProductUnit::where('product_purchase_order_product_id', $product->id)
                            ->where(function ($q) {
                                $q->where('unit_status', 'pending')->orWhereNull('unit_status')->orWhere('unit_status', '');
                            })->update(['unit_status' => 'instock']);
                    } else {
                        // Auto-generate units if none were provided
                        $existingUnitsCount = ProductPurchaseOrderProductUnit::where('product_purchase_order_product_id', $product->id)->count();
                        $qty = (int)($product->qty ?? 1);
                        for ($i = 1; $i <= $qty; $i++) {
                            $unitCode = $product->product_id . ($existingUnitsCount++);
                            $unit = new ProductPurchaseOrderProductUnit();
                            $unit->product_warehouse_id              = $product->product_warehouse_id;
                            $unit->product_purchase_order_id         = $order->id;
                            $unit->product_purchase_order_product_id = $product->id;
                            $unit->product_id                        = $product->product_id;
                            $unit->variant_combination_id            = $product->variant_combination_id ?? null;
                            $unit->code                              = $unitCode;
                            $unit->price                             = $product->purchase_price ?? 0;
                            $unit->unit_status                       = 'instock';
                            $unit->creator                           = $user->id;
                            $unit->slug                              = $product->id . ($existingUnitsCount++);
                            $unit->created_at                        = Carbon::now();
                            $unit->save();
                        }
                    }
                }
            }

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'Purchase Order updated successfully!',
                    'redirect' => route('ViewAllPurchaseProductOrder'),
                ]);
            }

            Toastr::success('Purchase Order updated successfully!', 'Success');
            return redirect()->route('ViewAllPurchaseProductOrder');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong! ' . $e->getMessage(),
                ], 500);
            }

            Toastr::error('Something went wrong! ' . $e->getMessage(), 'Error');
            return back()->withInput();
        }
    }

    public function apiEditPurchaseProduct($slug)
    {
        $data = ProductPurchaseOrder::with([
            'order_products' => fn($q) => $q->with([
                'product',
                'variantCombination',
                'units',          // load pending/instock barcode units
            ]),
        ])->where('slug', $slug)->first();

        return response()->json(['data' => $data]);
    }

    public function editPurchaseProductOrderConfirm($slug)
    {
        $data = ProductPurchaseOrder::with('order_products')->where('slug', $slug)->firstOrFail();

        if ($data->order_status == 'received') {
            Toastr::error('Order has been received already!', 'Error');
            return back();
        }

        if ($data->order_products->isEmpty()) {
            Toastr::error('Purchase order has no product lines to receive.', 'Error');
            return back();
        }

        $invalidProduct = $data->order_products->first(function ($product) {
            return empty($product->product_id) || (float)($product->qty ?? 0) <= 0;
        });

        if ($invalidProduct) {
            Toastr::error('Purchase order has invalid product lines. Please edit the order before confirming.', 'Error');
            return back();
        }

        if ($this->consumedPurchaseUnitCount($data->id) > 0) {
            Toastr::error('This purchase order has consumed barcode units and cannot be confirmed again.', 'Error');
            return back();
        }

        try {
            DB::beginTransaction();

        $data->order_status = 'received';
        $data->save();

        record_purchase_create_accounting($data);
        $accountingPosted = AcTransaction::where('ref_purchase_id', $data->id)
            ->where('transaction_type', 'PURCHASE_CREATE')
            ->exists();

        if (!$accountingPosted) {
            throw new \RuntimeException('Purchase accounting transaction was not created. Please configure purchase event mapping.');
        }

        $random_no = random_int(100, 999) . random_int(1000, 9999);
        $slug      = Str::orderedUuid() . uniqid() . $random_no;

        $stockHasColumns = [
            'has_variant'            => Schema::hasColumn('product_stocks', 'has_variant'),
            'variant_combination_key'=> Schema::hasColumn('product_stocks', 'variant_combination_key'),
            'variant_sku'            => Schema::hasColumn('product_stocks', 'variant_sku'),
            'variant_barcode'        => Schema::hasColumn('product_stocks', 'variant_barcode'),
            'variant_data'           => Schema::hasColumn('product_stocks', 'variant_data'),
            'variant_price'          => Schema::hasColumn('product_stocks', 'variant_price'),
            'variant_discount_price' => Schema::hasColumn('product_stocks', 'variant_discount_price'),
        ];
        $logHasColumns = [
            'has_variant'             => Schema::hasColumn('product_stock_logs', 'has_variant'),
            'variant_combination_key' => Schema::hasColumn('product_stock_logs', 'variant_combination_key'),
            'variant_sku'             => Schema::hasColumn('product_stock_logs', 'variant_sku'),
            'variant_data'            => Schema::hasColumn('product_stock_logs', 'variant_data'),
            'variant_combination_id'  => Schema::hasColumn('product_stock_logs', 'variant_combination_id'),
        ];

        foreach ($data->order_products as $product) {
            $product_stock = new ProductStock();
            $product_stock->product_warehouse_id              = $product->product_warehouse_id;
            $product_stock->product_warehouse_room_id         = $product->product_warehouse_room_id;
            $product_stock->product_warehouse_room_cartoon_id = $product->product_warehouse_room_cartoon_id;
            $product_stock->product_supplier_id               = $product->product_supplier_id;
            $product_stock->product_purchase_order_id         = $product->product_purchase_order_id;
            $product_stock->product_id                        = $product->product_id;

            if (Schema::hasColumn('product_stocks', 'variant_combination_id')) {
                $product_stock->variant_combination_id = $product->variant_combination_id ?? null;
            }

            $variantCombination = null;
            $hasVariant = !empty($product->variant_combination_id);
            if ($hasVariant) $variantCombination = ProductVariantCombination::find($product->variant_combination_id);

            if ($stockHasColumns['has_variant'])             $product_stock->has_variant             = $hasVariant;
            if ($stockHasColumns['variant_combination_key']) $product_stock->variant_combination_key = $variantCombination->combination_key ?? null;
            if ($stockHasColumns['variant_sku'])             $product_stock->variant_sku             = $variantCombination->sku ?? null;
            if ($stockHasColumns['variant_barcode'])         $product_stock->variant_barcode         = $variantCombination->barcode ?? null;
            if ($stockHasColumns['variant_data'])            $product_stock->variant_data            = $variantCombination?->variant_values;
            if ($stockHasColumns['variant_price'])           $product_stock->variant_price           = $variantCombination->price ?? null;
            if ($stockHasColumns['variant_discount_price'])  $product_stock->variant_discount_price  = $variantCombination->discount_price ?? null;

            $product_stock->date          = $data->date;
            $product_stock->qty           = $product->qty;
            $product_stock->purchase_price= $product->purchase_price;
            $product_stock->status        = 'active';
            $product_stock->slug          = $slug;
            $product_stock->save();

            $productModel = Product::find($product->product_id);

            if ($hasVariant) {
                if ($variantCombination) {
                    $variantCombination->stock = ($variantCombination->stock ?? 0) + $product_stock->qty;
                    if ($product->product_warehouse_id)              $variantCombination->product_warehouse_id              = $product->product_warehouse_id;
                    if ($product->product_warehouse_room_id)         $variantCombination->product_warehouse_room_id         = $product->product_warehouse_room_id;
                    if ($product->product_warehouse_room_cartoon_id) $variantCombination->product_warehouse_room_cartoon_id = $product->product_warehouse_room_cartoon_id;
                    $variantCombination->save();
                }
                if ($productModel) {
                    $productModel->stock = $productModel->variantCombinations()->sum('stock');
                    $productModel->save();
                }
            } else {
                if ($productModel) {
                    $productModel->stock = ($productModel->stock ?? 0) + $product_stock->qty;
                    $productModel->update();
                }
            }

            $logData = [
                'warehouse_id'        => $product->product_warehouse_id,
                'product_id'          => $product->product_id,
                'product_name'        => $productModel->name ?? null,
                'product_purchase_id' => $data->id,
                'quantity'            => $product->qty,
                'type'                => 'purchase',
            ];
            if ($logHasColumns['has_variant'])             $logData['has_variant']             = $hasVariant;
            if ($logHasColumns['variant_combination_key']) $logData['variant_combination_key'] = $variantCombination->combination_key ?? null;
            if ($logHasColumns['variant_sku'])             $logData['variant_sku']             = $variantCombination->sku ?? null;
            if ($logHasColumns['variant_data'])            $logData['variant_data']            = $variantCombination?->variant_values ?? null;
            if ($logHasColumns['variant_combination_id'])  $logData['variant_combination_id']  = $product->variant_combination_id ?? null;

            insert_stock_log($logData);

            /* ── Activate / create units from pre-saved barcodes ─ */
            // Treat NULL / empty unit_status as 'pending' to be safe
            $pendingUnits = ProductPurchaseOrderProductUnit::where('product_purchase_order_product_id', $product->id)
                ->where(function ($q) {
                    $q->where('unit_status', 'pending')
                      ->orWhereNull('unit_status')
                      ->orWhere('unit_status', '');
                })
                ->get();

            if ($pendingUnits->count() > 0) {
                // Activate pre-created units
                ProductPurchaseOrderProductUnit::where('product_purchase_order_product_id', $product->id)
                    ->where(function ($q) {
                        $q->where('unit_status', 'pending')
                          ->orWhereNull('unit_status')
                          ->orWhere('unit_status', '');
                    })
                    ->update(['unit_status' => 'instock']);
            } else {
                // Fall back: auto-generate units (no barcodes were supplied)
                $existingUnitsCount = ProductPurchaseOrderProductUnit::where('product_purchase_order_product_id', $product->id)->count();
                $qty = (int)($product->qty ?? 1);
                for ($i = 1; $i <= $qty; $i++) {
                    $unitCode = $product->product_id . ($existingUnitsCount++);
                    $unit = new ProductPurchaseOrderProductUnit();
                    $unit->product_warehouse_id              = $product->product_warehouse_id;
                    $unit->product_purchase_order_id         = $data->id;
                    $unit->product_purchase_order_product_id = $product->id;
                    $unit->product_id                        = $product->product_id;
                    $unit->variant_combination_id            = $product->variant_combination_id ?? null;
                    $unit->code                              = $unitCode;
                    $unit->price                             = $product->purchase_price ?? 0;
                    $unit->unit_status                       = 'instock';
                    $unit->creator                           = auth()->user()->id;
                    $unit->slug                              = $product->id . ($existingUnitsCount++);
                    $unit->created_at                        = Carbon::now();
                    $unit->save();
                }
            }
        }

            DB::commit();

            Toastr::success('Purchase Order confirmed and stock updated!', 'Success');
            return redirect()->route('ViewAllPurchaseProductOrder');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            Toastr::error('Purchase order confirm failed: ' . $e->getMessage(), 'Error');
            return back();
        }
    }

    public function deletePurchaseProductOrder($slug)
    {
        $data = ProductPurchaseOrder::where('slug', $slug)->firstOrFail();

        if ($data->order_status === 'received') {
            return response()->json([
                'success' => false,
                'error' => 'Confirmed purchase orders cannot be deleted. Create a purchase return or reversal instead.',
            ], 409);
        }

        if ($this->consumedPurchaseUnitCount($data->id) > 0) {
            return response()->json([
                'success' => false,
                'error' => 'This purchase order has sold/returned/lost/damaged barcode units and cannot be deleted.',
            ], 409);
        }

        DB::transaction(function () use ($data) {
            ProductPurchaseOrderProductUnit::where('product_purchase_order_id', $data->id)->delete();
            ProductPurchaseOrderProduct::where('product_purchase_order_id', $data->id)->delete();
            $data->delete();
        });

        return response()->json(['success' => 'Deleted successfully!', 'data' => 1]);
    }

    public function printPurchaseBarcode($purchase_id)
    {
        $purchaseOrder = ProductPurchaseOrder::find($purchase_id);
        if (!$purchaseOrder) {
            Toastr::error('Purchase order not found!', 'Error');
            return redirect()->route('ViewAllPurchaseProductOrder');
        }
        return view('backend.purchase_product_order.purchase_product_barcode_print', compact('purchase_id'));
    }

    public function apiGetPurchaseBarcodeUnits($purchase_id)
    {
        try {
            $units = ProductPurchaseOrderProductUnit::with([
                'product.category',
                'product.brand',
                'productPurchaseOrderProduct.product',
                'productPurchaseOrder.supplier',
                'productPurchaseOrder.warehouse',
                'variantCombination',
                'product'
            ])
            ->where('product_purchase_order_id', $purchase_id)->get();

            $generalInfo = GeneralInfo::where('id', 1)->first();
            $companyName = $generalInfo->company_name ?? '';

            $formattedUnits = $units->map(function ($unit) use ($companyName) {
                $dateValue = fn($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null;
                $productName = data_get($unit->product, 'name') ?? data_get($unit, 'productPurchaseOrderProduct.product_name')
                    ?? data_get($unit, 'product.name')
                    ?? 'N/A';
                $variantTitle = null;
                if ($unit->variant_combination_id && $unit->variantCombination) {
                    $variantValues = $unit->variantCombination->variant_values ?? [];
                    if (is_array($variantValues) && !empty($variantValues)) {
                        $variantTitle = collect($variantValues)->map(fn($v, $k) => ucfirst(str_replace('_', ' ', $k)) . ': ' . $v)->implode(' | ');
                    } else {
                        $variantTitle = $unit->variantCombination->combination_key ?? null;
                    }
                }
                $salesPrice = data_get($unit, 'product.price')
                    ?? data_get($unit, 'productPurchaseOrderProduct.purchase_price')
                    ?? $unit->price
                    ?? 0;
                $discountPrice = data_get($unit, 'product.discount_price') ?? 0;
                $sku = data_get($unit, 'product.sku')
                    ?? data_get($unit, 'productPurchaseOrderProduct.product.sku')
                    ?? '';

                return [
                    'id'            => $unit->id,
                    'code'          => $unit->code,
                    'product_name'  => $productName,
                    'product_image' => get_file_url() . '/' . (data_get($unit, 'product.image') ?? ''),
                    'variant_title' => $variantTitle,
                    'unit_status'   => $unit->unit_status,
                    'sales_price'   => (float)$salesPrice,
                    'discount_price' => (float)$discountPrice,
                    'sku'           => $sku,
                    'barcode_value' => $unit->code,
                    'serial_no'     => $unit->serial_no,
                    'imei_1'        => $unit->imei_1,
                    'imei_2'        => $unit->imei_2,
                    'supplier_warranty_start_date' => $dateValue($unit->supplier_warranty_start_date),
                    'supplier_warranty_end_date'   => $dateValue($unit->supplier_warranty_end_date),
                    'customer_warranty_start_date' => $dateValue($unit->customer_warranty_start_date),
                    'customer_warranty_end_date'   => $dateValue($unit->customer_warranty_end_date),
                    'warranty_note' => $unit->warranty_note,
                    'extra_attributes' => $unit->extra_attributes ?? [],
                    'company_name'  => $companyName,
                ];
            });

            $barcodeGroups = $units
                ->filter(fn($unit) => !empty($unit->code))
                ->groupBy(fn($unit) => $unit->code)
                ->map(function ($items, $barcode) use ($companyName) {
                $first = $items->first();
                $product = $first->product ?: data_get($first, 'productPurchaseOrderProduct.product');
                $purchaseOrder = $first->productPurchaseOrder;
                $purchaseLine = $first->productPurchaseOrderProduct;
                $productName = data_get($product, 'name') ?? data_get($purchaseLine, 'product_name') ?? 'N/A';
                $variantTitle = null;

                if ($first->variant_combination_id && $first->variantCombination) {
                    $variantValues = $first->variantCombination->variant_values ?? [];
                    $variantTitle = is_array($variantValues) && !empty($variantValues)
                        ? collect($variantValues)->map(fn($v, $k) => ucfirst(str_replace('_', ' ', $k)) . ': ' . $v)->implode(' | ')
                        : ($first->variantCombination->combination_key ?? null);
                }

                $availableQty = $items->where('unit_status', 'instock')->count();
                $soldQty = $items->where('unit_status', 'sold')->count();
                
                $salesPrice = data_get($product, 'price') ?? data_get($purchaseLine, 'purchase_price') ?? $first->price ?? 0;
                $discountPrice = data_get($product, 'discount_price') ?? 0;
                // has variant price > purchase line price > unit price > 0
                if($first->variant_combination_id && $first->variantCombination && data_get($first->variantCombination, 'price') !== null) {
                    $salesPrice = $first->variantCombination->price;
                    // discount price only from variant if it's set, otherwise from product
                    $discountPrice = data_get($first->variantCombination, 'discount_price') ?? $discountPrice;
                }
                
                $sku = data_get($product, 'sku') ?? data_get($purchaseLine, 'product.sku') ?? '';
                $dateValue = fn($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null;
                $unitDetails = $items->map(function ($unit) use ($dateValue) {
                    return [
                        'id' => $unit->id,
                        'code' => $unit->code,
                        'unit_status' => $unit->unit_status,
                        'serial_no' => $unit->serial_no,
                        'imei_1' => $unit->imei_1,
                        'imei_2' => $unit->imei_2,
                        'supplier_warranty_start_date' => $dateValue($unit->supplier_warranty_start_date),
                        'supplier_warranty_end_date' => $dateValue($unit->supplier_warranty_end_date),
                        'customer_warranty_start_date' => $dateValue($unit->customer_warranty_start_date),
                        'customer_warranty_end_date' => $dateValue($unit->customer_warranty_end_date),
                        'warranty_note' => $unit->warranty_note,
                        'extra_attributes' => $unit->extra_attributes ?? [],
                    ];
                })->values();
                $firstUnitDetail = $unitDetails->first() ?? [];

                $batches = $items
                    ->groupBy(fn($unit) => implode('|', [
                        $unit->batch_no ?? '',
                        optional($unit->productPurchaseOrder)->date ?? '',
                        $unit->price ?? '',
                        $unit->product_purchase_order_product_id ?? '',
                    ]))
                    ->map(function ($batchItems) {
                        $batchFirst = $batchItems->first();

                        return [
                            'purchase_date' => optional($batchFirst->productPurchaseOrder)->date,
                            'batch_no' => $batchFirst->batch_no,
                            'supplier' => optional(optional($batchFirst->productPurchaseOrder)->supplier)->name,
                            'warehouse' => optional(optional($batchFirst->productPurchaseOrder)->warehouse)->name,
                            'purchase_price' => (float)($batchFirst->price ?? data_get($batchFirst, 'productPurchaseOrderProduct.purchase_price') ?? 0),
                            'total_qty' => $batchItems->count(),
                            'available_qty' => $batchItems->where('unit_status', 'instock')->count(),
                            'sold_qty' => $batchItems->where('unit_status', 'sold')->count(),
                        ];
                    })
                    ->values();

                return [
                    'id' => md5($barcode . '-' . ($first->product_id ?? '') . '-' . ($first->variant_combination_id ?? '')),
                    'barcode' => $barcode,
                    'product_name' => $productName,
                    'product_image' => get_file_url() . '/' . (data_get($product, 'image') ?? ''),
                    'variant_title' => $variantTitle,
                    'has_variant' => !empty($first->variant_combination_id),
                    'sku' => $sku,
                    'serial_no' => $firstUnitDetail['serial_no'] ?? null,
                    'imei_1' => $firstUnitDetail['imei_1'] ?? null,
                    'imei_2' => $firstUnitDetail['imei_2'] ?? null,
                    'supplier_warranty_start_date' => $firstUnitDetail['supplier_warranty_start_date'] ?? null,
                    'supplier_warranty_end_date' => $firstUnitDetail['supplier_warranty_end_date'] ?? null,
                    'customer_warranty_start_date' => $firstUnitDetail['customer_warranty_start_date'] ?? null,
                    'customer_warranty_end_date' => $firstUnitDetail['customer_warranty_end_date'] ?? null,
                    'warranty_note' => $firstUnitDetail['warranty_note'] ?? null,
                    'has_serial' => $unitDetails->contains(fn($unit) => !empty($unit['serial_no'])),
                    'has_imei' => $unitDetails->contains(fn($unit) => !empty($unit['imei_1']) || !empty($unit['imei_2'])),
                    'has_warranty' => $unitDetails->contains(fn($unit) =>
                        !empty($unit['supplier_warranty_start_date']) ||
                        !empty($unit['supplier_warranty_end_date']) ||
                        !empty($unit['customer_warranty_start_date']) ||
                        !empty($unit['customer_warranty_end_date']) ||
                        !empty($unit['warranty_note'])
                    ),
                    'category' => data_get($product, 'category.name'),
                    'brand' => data_get($product, 'brand.name'),
                    'supplier' => data_get($purchaseOrder, 'supplier.name'),
                    'warehouse' => data_get($purchaseOrder, 'warehouse.name'),
                    'purchase_date' => data_get($purchaseOrder, 'date'),
                    'available_qty' => $availableQty,
                    'total_qty' => $items->count(),
                    'sold_qty' => $soldQty,
                    'unit_statuses' => $items->pluck('unit_status')->countBy(),
                    'sales_price' => (float)$salesPrice,
                    'discount_price' => (float)$discountPrice,
                    'purchase_price' => (float)($first->price ?? data_get($purchaseLine, 'purchase_price') ?? 0),
                    'company_name' => $companyName,
                    'print_quantity' => max($availableQty, 1),
                    'batches' => $batches,
                    'units' => $unitDetails,
                    'unit_ids' => $items->pluck('id')->values(),
                ];
                })
                ->sortBy('product_name')
                ->values();

            return response()->json([
                'success' => true,
                'data' => $formattedUnits,
                'groups' => $barcodeGroups,
                'company_name' => $companyName,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function apiUpdateBarcodeUnitCode(Request $request)
    {
        $request->validate([
            'unit_id' => 'required|exists:product_purchase_order_product_units,id',
            'code'    => 'required|string|max:10|unique:product_purchase_order_product_units,code,' . $request->unit_id,
        ]);
        $unit = ProductPurchaseOrderProductUnit::findOrFail($request->unit_id);
        $unit->code = $request->code;
        $unit->save();
        return response()->json(['success' => true, 'message' => 'Code updated successfully', 'data' => ['id' => $unit->id, 'code' => $unit->code]]);
    }

    /* ─────────────────────────────────────────────────────────────
     * PURCHASE INVOICE  (authenticated)
     * ───────────────────────────────────────────────────────────── */
    public function purchaseInvoice($slug)
    {
        $order = ProductPurchaseOrder::with([
            'supplier',
            'warehouse',
            'creator',
            'order_products.product',
            'order_products.variantCombination',
            'order_products.allUnits',
        ])->where('slug', $slug)->firstOrFail();

        $generalInfo = GeneralInfo::where('id', 1)->first() ?? new GeneralInfo();

        // return view('invoice.purchase.purchase_order_invoice', compact('order', 'generalInfo'))
        //     ->with('isPublic', false);
        return view('backend.purchase_product_order.purchase_invoice', compact('order', 'generalInfo'))
            ->with('isPublic', false);
    }

    /* ─────────────────────────────────────────────────────────────
     * PUBLIC PURCHASE INVOICE  (no auth required)
     * ───────────────────────────────────────────────────────────── */
    public function publicPurchaseInvoice($slug)
    {
        $order = ProductPurchaseOrder::with([
            'supplier',
            'warehouse',
            'creator',
            'order_products.product',
            'order_products.variantCombination',
            'order_products.allUnits',
        ])->where('slug', $slug)->firstOrFail();

        $generalInfo = GeneralInfo::where('id', 1)->first() ?? new GeneralInfo();

        return view('invoice.purchase.public_invoice', compact('order', 'generalInfo'));
    }

    /* ─────────────────────────────────────────────────────────────
     * SEND PURCHASE INVOICE EMAIL
     * ───────────────────────────────────────────────────────────── */
    public function sendPurchaseInvoiceEmail(Request $request, $slug)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $order = ProductPurchaseOrder::with([
            'supplier',
            'warehouse',
            'creator',
            'order_products.product',
            'order_products.variantCombination',
            'order_products.allUnits',
        ])->where('slug', $slug)->firstOrFail();

        $generalInfo = GeneralInfo::where('id', 1)->first() ?? new GeneralInfo();

        try {
            Mail::to($request->email)->send(new PurchaseInvoiceEmail($order, $generalInfo));
            return response()->json(['success' => true, 'message' => 'Invoice sent successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
