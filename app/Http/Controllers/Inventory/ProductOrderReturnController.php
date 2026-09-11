<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Customer\Models\Customer;
use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductOrderProduct;
use App\Models\ProductOrderRefund;
use App\Models\ProductOrderReturn;
use App\Models\ProductOrderReturnProduct;
use App\Models\ProductPurchaseOrderProductUnit;
use App\Services\Inventory\ReturnRefundAccountingService;
use App\Services\FbMarketing\FbmOrderAttributionBridgeService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Brian2694\Toastr\Facades\Toastr;
use DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductOrderReturnController extends Controller
{
    /**
     * Display a listing of returns
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ProductOrderReturn::with(['creator', 'return_products', 'customer', 'originalOrder'])
                ->orderBy('id', 'desc')
                ->get();

            return Datatables::of($data)
                ->editColumn('return_date', function ($data) {
                    return date("Y-m-d", strtotime($data->return_date));
                })
                ->editColumn('customer', function ($data) {
                    return $data->customer ? $data->customer->name : 'N/A';
                })
                ->editColumn('original_order', function ($data) {
                    return $data->originalOrder ? $data->originalOrder->order_code : 'N/A';
                })
                ->editColumn('status', function ($data) {
                    return $data->status == "active" ? 'Active' : 'Inactive';
                })
                ->editColumn('return_status', function ($data) {
                    $badge = '';
                    if ($data->return_status == 'approved') {
                        $badge = '<span class="badge badge-success">Approved</span>';
                    } elseif ($data->return_status == 'pending') {
                        $badge = '<span class="badge badge-warning">Pending</span>';
                    } else {
                        $badge = '<span class="badge badge-danger">Rejected</span>';
                    }
                    return $badge;
                })
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    $btn = '<div class="dropdown">';
                    $btn .= '<button class="btn-sm btn-primary dropdown-toggle rounded" type="button" id="actionDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                    $btn .= '<i class="fas fa-cog"></i> Actions';
                    $btn .= '</button>';
                    $btn .= '<div class="dropdown-menu" aria-labelledby="actionDropdown">';
                    
                    // View Return
                    $btn .= '<a class="dropdown-item" href="' . route('ShowProductOrderReturn', $data->slug) . '" target="_blank"><i class="fas fa-eye text-info"></i> View Return</a>';
                    
                    // Edit
                    $btn .= '<a class="dropdown-item" href="' . route('EditProductOrderReturn', $data->slug) . '"><i class="fas fa-edit text-warning"></i> Edit</a>';
                    
                    // Print
                    $btn .= '<a class="dropdown-item" href="' . route('PrintProductOrderReturn', $data->slug) . '" target="_blank"><i class="fas fa-print text-primary"></i> Print</a>';
                    
                    // Delete
                    $btn .= '<div class="dropdown-divider"></div>';
                    $btn .= '<a class="dropdown-item deleteBtn" href="javascript:void(0)" data-toggle="tooltip" data-id="' . $data->slug . '" data-original-title="Delete"><i class="fas fa-trash-alt text-danger"></i> Delete</a>';
                    
                    $btn .= '</div>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action', 'return_status'])
                ->make(true);
        }
        return view('backend.product_order_return.index');
    }

    /**
     * Show the form for creating a new return
     */
    public function create($slug)
    {
        $order = ProductOrder::with(['order_products', 'customer', 'warehouse'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Check if order is invoiced or delivered
        if ($order->order_status != 'invoiced' && $order->order_status != 'delivered') {
            Toastr::warning('Only invoiced/delivered orders can be returned!', 'Warning');
            return redirect()->route('ViewAllProductOrder');
        }

        // Get available return quantities for each product
        $availableQuantities = $this->getAvailableReturnQuantities($order->id);

        $payment_methods = DbPaymentType::where('status', 'active')->get();

        // Check if any products are available for return
        $hasAvailableProducts = false;
        foreach ($availableQuantities as $qty) {
            if ($qty > 0) {
                $hasAvailableProducts = true;
                break;
            }
        }

        if (!$hasAvailableProducts) {
            Toastr::warning('All products from this order have already been returned!', 'Warning');
            return redirect()->route('ViewAllProductOrder');
        }

        $return_code = $this->generateReturnCode();
        
        return view('backend.product_order_return.create', compact('order', 'availableQuantities', 'return_code', 'payment_methods'));
    }

    /**
     * Store a newly created return
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_order_id' => ['required', 'exists:product_orders,id'],
            'return_date' => ['required', 'date'],
            'return_code' => ['required', 'unique:product_order_returns,return_code'],
            'return_products' => 'required|array|min:1',
            'return_type' => 'nullable|in:advance_only,instant_refund',
            'refund_payment_type_id' => 'required_if:return_type,instant_refund|nullable|exists:db_paymenttypes,id',
        ], [
            'return_products.required' => 'No products selected for return.',
            'return_code.unique' => 'Return code already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $order = ProductOrder::with('order_products')->findOrFail($request->product_order_id);
            $returnType = $request->get('return_type', 'advance_only');

            // Validate return quantities
            $availableQuantities = $this->getAvailableReturnQuantities($order->id);
            
            foreach ($request->return_products as $returnProduct) {
                $productId = $returnProduct['product_id'];
                $returnQty = $returnProduct['qty'];
                
                if ($returnQty > ($availableQuantities[$productId] ?? 0)) {
                    DB::rollBack();
                    $product = Product::find($productId);
                    return response()->json([
                        'success' => false,
                        'message' => "Return quantity exceeds available quantity for {$product->name}"
                    ], 422);
                }
            }

            $random_no = random_int(100, 999) . random_int(1000, 9999);
            $slug = Str::orderedUuid() . uniqid() . $random_no;
            $user = auth()->user();

            // Create return record
            $return = new ProductOrderReturn();
            $return->product_order_id = $order->id;
            $return->return_code = $request->return_code;
            $return->product_warehouse_id = $order->product_warehouse_id;
            $return->product_warehouse_room_id = $order->product_warehouse_room_id;
            $return->product_warehouse_room_cartoon_id = $order->product_warehouse_room_cartoon_id;
            $return->customer_id = $order->customer_id;
            $return->return_date = $request->return_date;
            $return->return_reason = $request->return_reason;
            
            $return->other_charges = $request->other_charges ?? [];
            $return->other_charge_amount = $request->other_charge_amount ?? 0;
            
            $return->discount_type = $request->discount_type;
            $return->discount_amount = $request->discount_amount ?? 0;
            $return->calculated_discount_amount = $request->calculated_discount_amount ?? 0;
            
            $return->round_off_from_total = $request->round_off_from_total ?? 0;
            $return->decimal_round_off = $request->decimal_round_off ?? 0;
            
            $return->subtotal = $request->subtotal_amt ?? 0;
            $return->total = $request->grand_total_amt ?? 0;
            
            $return->refund_method = 'advance_payment';
            $return->return_type = $returnType;
            $return->return_invoice_no = $request->return_code;
            $return->refund_payment_type_id = $returnType === 'instant_refund' ? $request->refund_payment_type_id : null;
            $return->refunded_amount = 0;
            $return->refund_due_amount = $return->total;
            $return->refund_status = 'pending';
            $return->inventory_restocked = 1;
            $return->restocked_at = Carbon::now();
            $return->return_status = 'approved';
            
            $return->note = $request->note;
            $return->creator = $user->id;
            $return->status = 'active';
            $return->created_at = Carbon::now();
            $return->save();

            // Create return products
            foreach ($request->return_products as $returnProduct) {
                $product_slug = Str::orderedUuid() . $random_no . $return->id . uniqid();
                $product = Product::find($returnProduct['product_id']);

                ProductOrderReturnProduct::create([
                    'product_order_return_id' => $return->id,
                    'product_order_product_id' => $returnProduct['order_product_id'] ?? null,
                    'product_warehouse_id' => $order->product_warehouse_id,
                    'product_warehouse_room_id' => $order->product_warehouse_room_id,
                    'product_warehouse_room_cartoon_id' => $order->product_warehouse_room_cartoon_id,
                    'product_id' => $returnProduct['product_id'],
                    'product_name' => $product->name,
                    'qty' => $returnProduct['qty'],
                    'sale_price' => $returnProduct['sale_price'],
                    'discount_type' => $returnProduct['discount_type'] ?? 'in_percentage',
                    'discount_amount' => $returnProduct['discount_amount'] ?? 0,
                    'tax' => $returnProduct['tax'] ?? 0,
                    'total_price' => $returnProduct['total_price'],
                    'product_price' => $product->discount_price ? $product->discount_price : $product->price,
                    'slug' => $product_slug,
                    'creator' => $user->id,
                ]);

                // Update product stock (add back)
                $product->stock += $returnProduct['qty'];
                $product->save();

                // Insert stock log
                insert_stock_log([
                    'warehouse_id' => $order->product_warehouse_id,
                    'product_id' => $returnProduct['product_id'],
                    'product_name' => $product->name,
                    'product_return_id' => $return->id,
                    'quantity' => $returnProduct['qty'],
                    'type' => 'return',
                ]);

                $product_unit = ProductPurchaseOrderProductUnit::where('sale_id', $order->id)->where('product_id', $returnProduct['product_id'])->first();
                if ($product_unit) {
                    $product_unit->return_id = $return->id;
                    $product_unit->unit_status = 'instock';
                    $product_unit->save();
                }
            }

            $return->slug = $return->id . $slug;
            $return->save();

            $return->load('return_products');
            $accounting = app(ReturnRefundAccountingService::class);
            $accounting->postReturn($return);

            if ($returnType === 'instant_refund') {
                $paymentType = DbPaymentType::where('status', 'active')->findOrFail($request->refund_payment_type_id);
                $accounting->createRefund($return->fresh(), $paymentType, (float) $return->total, $request->return_date, $request->note);
            }

            DB::commit();

            FbmOrderAttributionBridgeService::tryReconcileProductOrderById((int) $order->id, 'product_order_return_created');

            Toastr::success('Return has been created successfully!', 'Success');
            return response()->json([
                'success' => true,
                'message' => 'Return has been created successfully!',
                'return' => $return,
                'redirect' => route('ViewAllProductOrderReturns')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Something went wrong! ' . $e->getMessage(), 'Error');
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified return
     */
    public function show($slug)
    {
        $return = ProductOrderReturn::with(['return_products', 'customer', 'warehouse', 'originalOrder', 'refunds.paymentType'])
            ->where('slug', $slug)
            ->firstOrFail();

        $paymentMethods = DbPaymentType::where('status', 'active')->orderBy('payment_type')->get();

        // Company information
        $company = [
            'name' => 'BME Trading Company',
            'address' => '123 Business District, Dhaka-1000, Bangladesh',
            'phone' => '+880 1700-000000',
            'email' => 'info@bmetrading.com',
            'website' => 'www.bmetrading.com',
            'logo' => '/logo.png'
        ];

        $qrData = "Return: {$return->return_code}\nDate: {$return->return_date}\nTotal: {$return->total} BDT";

        return view('backend.product_order_return.show', compact('return', 'company', 'qrData', 'paymentMethods'));
    }

    public function storeRefund(Request $request, $slug)
    {
        $return = ProductOrderReturn::with(['originalOrder', 'refunds'])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $remaining = max(0, (float) $return->total - (float) $return->refunded_amount);

        $validator = Validator::make($request->all(), [
            'refund_date' => ['required', 'date'],
            'refund_amount' => ['required', 'numeric', 'min:0.01', 'max:' . $remaining],
            'payment_type_id' => ['required', 'exists:db_paymenttypes,id'],
            'note' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            app(ReturnRefundAccountingService::class)->postReturn($return);

            $paymentType = DbPaymentType::where('status', 'active')->findOrFail($request->payment_type_id);
            app(ReturnRefundAccountingService::class)->createRefund(
                $return->fresh(),
                $paymentType,
                (float) $request->refund_amount,
                $request->refund_date,
                $request->note
            );

            DB::commit();
            Toastr::success('Refund has been posted successfully!', 'Success');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Refund failed! ' . $e->getMessage(), 'Error');
        }

        return redirect()->back();
    }

    public function showRefund($slug)
    {
        $refund = ProductOrderRefund::with(['return.return_products', 'order', 'customer', 'paymentType', 'account'])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('backend.product_order_return.refund_voucher', [
            'refund' => $refund,
            'company' => $this->companyInfo(),
            'printMode' => false,
        ]);
    }

    public function printRefund($slug)
    {
        $refund = ProductOrderRefund::with(['return.return_products', 'order', 'customer', 'paymentType', 'account'])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('backend.product_order_return.refund_voucher', [
            'refund' => $refund,
            'company' => $this->companyInfo(),
            'printMode' => true,
        ]);
    }

    public function reverseRefund(Request $request, $slug)
    {
        try {
            DB::beginTransaction();
            $refund = ProductOrderRefund::where('slug', $slug)->where('status', 'active')->firstOrFail();
            app(ReturnRefundAccountingService::class)->reverseRefund($refund, $request->note);
            DB::commit();
            Toastr::success('Refund reversed successfully!', 'Success');
            return redirect()->route('ShowProductOrderReturn', $refund->return->slug);
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Refund reversal failed! ' . $e->getMessage(), 'Error');
            return redirect()->back();
        }
    }

    public function reverseReturn(Request $request, $slug)
    {
        try {
            DB::beginTransaction();
            $return = ProductOrderReturn::where('slug', $slug)->where('status', 'active')->firstOrFail();
            app(ReturnRefundAccountingService::class)->reverseReturn($return, $request->note);
            DB::commit();
            Toastr::success('Return reversed successfully!', 'Success');
            return redirect()->route('ViewAllProductOrderReturns');
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Return reversal failed! ' . $e->getMessage(), 'Error');
            return redirect()->back();
        }
    }

    /**
     * Show the form for editing the specified return
     */
    public function edit($slug)
    {
        $returnData = ProductOrderReturn::with(['return_products', 'originalOrder.order_products'])
            ->where('slug', $slug)
            ->firstOrFail();

        $order = $returnData->originalOrder;
        $isLocked = (int) ($returnData->is_accounting_posted ?? 0) === 1 || (float) ($returnData->refunded_amount ?? 0) > 0;

        // Get available return quantities (excluding current return)
        $availableQuantities = $this->getAvailableReturnQuantities($order->id, $returnData->id);

        return view('backend.product_order_return.edit', compact('returnData', 'order', 'availableQuantities', 'isLocked'));
    }

    /**
     * Update the specified return
     */
    public function update(Request $request, $slug)
    {
        $validator = Validator::make($request->all(), [
            'product_order_return_id' => ['required', 'exists:product_order_returns,id'],
            'return_date' => ['required', 'date'],
            'return_products' => 'required|array|min:1',
        ], [
            'return_products.required' => 'No products selected for return.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $return = ProductOrderReturn::findOrFail($request->product_order_return_id);
            if ((int) ($return->is_accounting_posted ?? 0) === 1 || (float) ($return->refunded_amount ?? 0) > 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'This return is already posted to accounts. Please create a reversal instead of editing it.'
                ], 422);
            }

            $order = ProductOrder::with('order_products')->findOrFail($return->product_order_id);

            // Validate return quantities (excluding current return)
            $availableQuantities = $this->getAvailableReturnQuantities($order->id, $return->id);
            
            foreach ($request->return_products as $returnProduct) {
                $productId = $returnProduct['product_id'];
                $returnQty = $returnProduct['qty'];
                
                if ($returnQty > ($availableQuantities[$productId] ?? 0)) {
                    DB::rollBack();
                    $product = Product::find($productId);
                    return response()->json([
                        'success' => false,
                        'message' => "Return quantity exceeds available quantity for {$product->name}"
                    ], 422);
                }
            }

            // Reverse previous stock changes
            foreach ($return->return_products as $oldProduct) {
                $product = Product::find($oldProduct->product_id);
                if ($product) {
                    $product->stock -= $oldProduct->qty; // Remove the previously added stock
                    $product->save();
                }
            }

            // Delete old return products
            ProductOrderReturnProduct::where('product_order_return_id', $return->id)->delete();

            $user = auth()->user();

            // Update return record
            $return->return_date = $request->return_date;
            $return->return_reason = $request->return_reason;
            
            $return->other_charges = $request->other_charges ?? [];
            $return->other_charge_amount = $request->other_charge_amount ?? 0;
            
            $return->discount_type = $request->discount_type;
            $return->discount_amount = $request->discount_amount ?? 0;
            $return->calculated_discount_amount = $request->calculated_discount_amount ?? 0;
            
            $return->round_off_from_total = $request->round_off_from_total ?? 0;
            $return->decimal_round_off = $request->decimal_round_off ?? 0;
            
            $return->subtotal = $request->subtotal_amt ?? 0;
            $return->total = $request->grand_total_amt ?? 0;
            
            $return->refund_method = 'advance_payment';
            $return->note = $request->note;
            $return->updated_at = Carbon::now();
            $return->save();

            $random_no = random_int(100, 999) . random_int(1000, 9999);

            // Create new return products and update stock
            foreach ($request->return_products as $returnProduct) {
                $product_slug = Str::orderedUuid() . $random_no . $return->id . uniqid();
                $product = Product::find($returnProduct['product_id']);

                ProductOrderReturnProduct::create([
                    'product_order_return_id' => $return->id,
                    'product_order_product_id' => $returnProduct['order_product_id'] ?? null,
                    'product_warehouse_id' => $order->product_warehouse_id,
                    'product_warehouse_room_id' => $order->product_warehouse_room_id,
                    'product_warehouse_room_cartoon_id' => $order->product_warehouse_room_cartoon_id,
                    'product_id' => $returnProduct['product_id'],
                    'product_name' => $product->name,
                    'qty' => $returnProduct['qty'],
                    'sale_price' => $returnProduct['sale_price'],
                    'discount_type' => $returnProduct['discount_type'] ?? 'in_percentage',
                    'discount_amount' => $returnProduct['discount_amount'] ?? 0,
                    'tax' => $returnProduct['tax'] ?? 0,
                    'total_price' => $returnProduct['total_price'],
                    'product_price' => $product->discount_price ? $product->discount_price : $product->price,
                    'slug' => $product_slug,
                    'creator' => $user->id,
                ]);

                // Update product stock (add back)
                $product->stock += $returnProduct['qty'];
                $product->save();

                // Insert stock log
                insert_stock_log([
                    'warehouse_id' => $order->product_warehouse_id,
                    'product_id' => $returnProduct['product_id'],
                    'product_name' => $product->name,
                    'product_return_id' => $return->id,
                    'quantity' => $returnProduct['qty'],
                    'type' => 'return',
                ]);
            }

            DB::commit();

            Toastr::success('Return has been updated successfully!', 'Success');
            return response()->json([
                'success' => true,
                'message' => 'Return has been updated successfully!',
                'return' => $return,
                'redirect' => route('ViewAllProductOrderReturns')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Something went wrong! ' . $e->getMessage(), 'Error');
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified return (soft delete)
     */
    public function destroy($slug)
    {
        try {
            DB::beginTransaction();

            $return = ProductOrderReturn::with('return_products')->where('slug', $slug)->firstOrFail();

            if ((int) ($return->is_accounting_posted ?? 0) === 1) {
                app(ReturnRefundAccountingService::class)->reverseReturn($return, 'Deleted from return list');
                DB::commit();
                return response()->json([
                    'success' => 'Reversed successfully!',
                    'data' => 1
                ]);
            }

            // Reverse stock changes
            foreach ($return->return_products as $returnProduct) {
                $product = Product::find($returnProduct->product_id);
                if ($product) {
                    $product->stock -= $returnProduct->qty; // Remove the previously added stock
                    $product->save();
                }
            }

            // Soft delete
            $return->status = 'inactive';
            $return->save();

            app(ReturnRefundAccountingService::class)->refreshOrderReturnSummary(ProductOrder::findOrFail($return->product_order_id));

            DB::commit();

            return response()->json([
                'success' => 'Deleted successfully!',
                'data' => 1
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting return: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print return invoice
     */
    public function printReturn($slug)
    {
        $return = ProductOrderReturn::with(['return_products', 'customer', 'warehouse', 'originalOrder'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Company information
        $company = [
            'name' => 'BME Trading Company',
            'address' => '123 Business District, Dhaka-1000, Bangladesh',
            'phone' => '+880 1700-000000',
            'email' => 'info@bmetrading.com',
            'website' => 'www.bmetrading.com',
            'logo' => '/logo.png'
        ];

        $qrData = "Return: {$return->return_code}\nDate: {$return->return_date}\nTotal: {$return->total} BDT";

        return view('backend.product_order_return.print', compact('return', 'company', 'qrData'));
    }

    /**
     * Get return history for an order (API endpoint)
     */
    public function getReturnHistory($orderId)
    {
        try {
            $returns = ProductOrderReturn::with('return_products')
                ->where('product_order_id', $orderId)
                ->where('status', 'active')
                ->orderBy('return_date', 'desc')
                ->get();

            $history = [];
            foreach ($returns as $return) {
                foreach ($return->return_products as $product) {
                    $history[] = [
                        'return_code' => $return->return_code,
                        'return_date' => $return->return_date,
                        'product_name' => $product->product_name,
                        'qty' => $product->qty,
                        'total' => $product->total_price,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'history' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching return history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get original invoice data (API endpoint)
     */
    public function getOriginalInvoice($orderId)
    {
        try {
            $order = ProductOrder::with(['order_products', 'customer'])
                ->findOrFail($orderId);

            return response()->json([
                'success' => true,
                'order' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching original invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Get available return quantities for an order
     */
    private function getAvailableReturnQuantities($orderId, $excludeReturnId = null)
    {
        $order = ProductOrder::with('order_products')->findOrFail($orderId);
        $availableQuantities = [];

        foreach ($order->order_products as $orderProduct) {
            // Get total already returned for this product
            $returnedQty = ProductOrderReturnProduct::whereHas('return', function ($query) use ($orderId, $excludeReturnId) {
                $query->where('product_order_id', $orderId)
                      ->where('status', 'active');
                if ($excludeReturnId) {
                    $query->where('id', '!=', $excludeReturnId);
                }
            })
            ->where('product_id', $orderProduct->product_id)
            ->sum('qty');

            $availableQuantities[$orderProduct->product_id] = $orderProduct->qty - $returnedQty;
        }

        return $availableQuantities;
    }

    /**
     * Helper: Generate unique return code
     */
    private function generateReturnCode()
    {
        $year = Carbon::now()->format('y');
        $month = Carbon::now()->format('m');
        $prefix = 'R' . $year . $month;

        $latestReturn = ProductOrderReturn::where('return_code', 'like', $prefix . '%')
            ->orderBy('return_code', 'desc')
            ->first();

        if ($latestReturn) {
            $lastNumber = intval(substr($latestReturn->return_code, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    private function companyInfo()
    {
        return [
            'name' => 'BME Trading Company',
            'address' => '123 Business District, Dhaka-1000, Bangladesh',
            'phone' => '+880 1700-000000',
            'email' => 'info@bmetrading.com',
            'website' => 'www.bmetrading.com',
            'logo' => '/logo.png'
        ];
    }
}

