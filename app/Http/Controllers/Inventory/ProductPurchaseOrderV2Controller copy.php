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
use DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ProductPurchaseOrderV2Controller extends Controller
{
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
                    $rawCode = trim((string)($barcodes[$ui] ?? ''));
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
                    : url()->current();
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
            $data = ProductPurchaseOrder::with('creator', 'order_products')->orderBy('id', 'desc');
            return DataTables::of($data)
                ->editColumn('status', fn($d) => $d->status == 'active' ? 'Active' : 'Inactive')
                ->editColumn('created_at', fn($d) => date('Y-m-d', strtotime($d->created_at)))
                ->editColumn('total', function ($d) {
                    $total_products = $d->order_products->count();
                    return "<div>
                        <div>Total Products: <span class='badge badge-info'>{$total_products}</span></div>
                        <div>Total: <span class='badge badge-info'>{$d->total}</span></div>
                    </div>";
                })
                ->addIndexColumn()
                ->addColumn('product', function ($d) {
                    return collect($d->order_products)
                        ->values()
                        ->map(function ($item, $index) {
                            return ($index + 1) . '. <strong>' . $item->product_name . '</strong>';
                        })
                        ->implode('<br>');
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
                ->rawColumns(['action', 'total','product'])
                ->make(true);
        }
        return view('backend.purchase_product_order.view');
    }

    public function editPurchaseProductOrder($slug)
    {
        $data                        = ProductPurchaseOrder::where('slug', $slug)->first();
        $productWarehouses           = ProductWarehouse::where('status', 'active')->get();
        $productWarehouseRooms       = ProductWarehouseRoom::where('product_warehouse_id', $data->product_warehouse_id)->where('status', 'active')->get();
        $productWarehouseRoomCartoon = ProductWarehouseRoomCartoon::where('product_warehouse_id', $data->product_warehouse_id)->where('product_warehouse_room_id', $data->product_warehouse_room_id)->where('status', 'active')->get();
        $suppliers                   = ProductSupplier::where('status', 'active')->get();
        $other_charges_types         = ProductPurchaseOtherCharge::where('status', 'active')->get();

        return view('backend.purchase_product_order.edit', compact('data', 'productWarehouses', 'productWarehouseRooms', 'productWarehouseRoomCartoon', 'suppliers', 'other_charges_types'));
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

        if ($order->order_status === 'received' && (int)$user->user_type !== 1) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Only admin can edit a confirmed purchase order.'], 403);
            }
            Toastr::error('Only admin can edit a confirmed purchase order.', 'Error');
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
                    $rawCode = trim((string)($barcodes[$ui] ?? ''));
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
        $data = ProductPurchaseOrder::with('order_products')->where('slug', $slug)->first();

        if ($data->order_status == 'received') {
            Toastr::error('Order has been received already!', 'Error');
            return back();
        }

        $data->order_status = 'received';
        $data->save();

        record_purchase_create_accounting($data);

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

        Toastr::success('Purchase Order confirmed and stock updated!', 'Success');
        return redirect()->route('ViewAllPurchaseProductOrder');
    }

    public function deletePurchaseProductOrder($slug)
    {
        $data = ProductPurchaseOrder::where('slug', $slug)->firstOrFail();

        $product_units = ProductPurchaseOrderProductUnit::where('product_purchase_order_id', $data->id)->get();
        foreach ($product_units as $unit) {
            $vc = ProductVariantCombination::find($unit->variant_combination_id);
            if ($vc && $vc->stock > 0) { $vc->stock = max(0, ($vc->stock ?? 0) - 1); $vc->save(); }
            $ps = \App\Http\Controllers\Inventory\Models\ProductStock::where('variant_combination_id', $unit->variant_combination_id)->first();
            if ($ps && $ps->qty > 0) { $ps->qty = max(0, ($ps->qty ?? 0) - 1); $ps->save(); }
        }

        ProductPurchaseOrderProductUnit::where('product_purchase_order_id', $data->id)->delete();
        ProductPurchaseOrderProduct::where('product_purchase_order_id', $data->id)->delete();
        $data->delete();

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
        $units = ProductPurchaseOrderProductUnit::with(['product', 'productPurchaseOrderProduct.product', 'variantCombination'])
            ->where('product_purchase_order_id', $purchase_id)->get();

        $formattedUnits = $units->map(function ($unit) {
            $productName = $unit->productPurchaseOrderProduct->product_name ?? $unit->product->name ?? 'N/A';
            $variantTitle = null;
            if ($unit->variant_combination_id && $unit->variantCombination) {
                $variantValues = $unit->variantCombination->variant_values ?? [];
                if (is_array($variantValues) && !empty($variantValues)) {
                    $variantTitle = collect($variantValues)->map(fn($v, $k) => ucfirst(str_replace('_', ' ', $k)) . ': ' . $v)->implode(' | ');
                } else {
                    $variantTitle = $unit->variantCombination->combination_key ?? null;
                }
            }
            $salesPrice = $unit->product->price ?? $unit->productPurchaseOrderProduct->purchase_price ?? $unit->price ?? 0;
            $sku = $unit->product->sku ?? $unit->productPurchaseOrderProduct->product->sku ?? '';
            return [
                'id'            => $unit->id,
                'code'          => $unit->code,
                'product_name'  => $productName,
                'product_image' => get_file_url() . '/' . ($unit->product->image ?? ''),
                'variant_title' => $variantTitle,
                'unit_status'   => $unit->unit_status,
                'sales_price'   => (float)$salesPrice,
                'sku'           => $sku,
                'barcode_value' => $unit->code,
            ];
        });

        return response()->json(['success' => true, 'data' => $formattedUnits]);
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