<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Customer\Models\Customer;
use App\Http\Controllers\Account\Models\DbCustomerPayment;
use App\Models\Product;
use App\Models\ManualProductReturn;
use App\Models\ManualProductReturnItem;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Brian2694\Toastr\Facades\Toastr;
use DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\Inventory\ReturnRefundAccountingService;

class ManualProductReturnController extends Controller
{
    /**
     * Show create form
     */
    public function create()
    {
        $return_code = $this->generateReturnCode();
        
        return view('backend.manual_product_return.create', compact('return_code'));
    }

    public function customerSearch(Request $request)
    {
        $page = max((int) $request->get('page', 1), 1);
        $term = trim((string) $request->get('term', ''));
        $perPage = 10;

        $query = Customer::where('status', 'active')
            ->select('id', 'name', 'phone');

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        $customers = $query->orderBy('name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage + 1)
            ->get();

        return response()->json([
            'results' => $customers->take($perPage)->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'text' => trim($customer->name . ($customer->phone ? ' - ' . $customer->phone : '')),
                ];
            })->values(),
            'pagination' => ['more' => $customers->count() > $perPage],
        ]);
    }

    public function productSearch(Request $request)
    {
        $page = max((int) $request->get('page', 1), 1);
        $term = trim((string) $request->get('term', ''));
        $perPage = 10;

        $query = Product::where('status', '1')
            ->select('id', 'name', 'price', 'discount_price');

        if ($term !== '') {
            $query->where('name', 'like', "%{$term}%");
        }

        $products = $query->orderBy('name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage + 1)
            ->get();

        return response()->json([
            'results' => $products->take($perPage)->map(function ($product) {
                $price = (float) ($product->discount_price ?: $product->price ?: 0);
                return [
                    'id' => $product->id,
                    'text' => $product->name,
                    'price' => $price,
                ];
            })->values(),
            'pagination' => ['more' => $products->count() > $perPage],
        ]);
    }

    /**
     * Store manual return
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'return_date' => 'required|date',
            'return_code' => 'required|unique:manual_product_returns,return_code',
            'return_items' => 'required|array|min:1',
            'return_items.*.product_id' => 'required|exists:products,id',
            'return_items.*.qty' => 'required|integer|min:1',
            'return_items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'return_items.required' => 'No products selected for return.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $customer = Customer::findOrFail($request->customer_id);
            $user = auth()->user();
            $random_no = random_int(100, 999) . random_int(1000, 9999);
            $slug = Str::orderedUuid() . uniqid() . $random_no;

            // Calculate totals
            $subtotal = 0;
            foreach ($request->return_items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $unitPrice = (float) ($item['unit_price'] ?? ($product->discount_price ?: $product->price));
                $subtotal += (int) $item['qty'] * $unitPrice;
            }

            // Create return record
            $return = new ManualProductReturn();
            $return->return_code = $request->return_code;
            $return->customer_id = $customer->id;
            $return->return_date = $request->return_date;
            $return->return_reason = $request->return_reason;
            $return->subtotal = $subtotal;
            $return->total = $subtotal;
            $return->refund_method = 'wallet';
            $return->refund_status = 'completed';
            $return->return_status = 'approved';
            $return->note = $request->note;
            $return->creator = $user->id;
            $return->status = 'active';
            $return->created_at = Carbon::now();
            $return->save();

            // Create return items
            foreach ($request->return_items as $item) {
                $item_slug = Str::orderedUuid() . $random_no . $return->id . uniqid();
                $product = Product::findOrFail($item['product_id']);
                $unitPrice = (float) ($item['unit_price'] ?? ($product->discount_price ?: $product->price));
                
                ManualProductReturnItem::create([
                    'manual_product_return_id' => $return->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'qty' => $item['qty'],
                    'unit_price' => $unitPrice,
                    'total_price' => $item['qty'] * $unitPrice,
                    'slug' => $item_slug,
                    'creator' => $user->id,
                ]);

                $product->stock += $item['qty'];
                $product->save();

                insert_stock_log([
                    'warehouse_id' => null,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_return_id' => $return->id,
                    'quantity' => $item['qty'],
                    'type' => 'return',
                ]);
            }

            $return->slug = $return->id . $slug;
            $return->save();

            app(ReturnRefundAccountingService::class)->postManualReturn($return);

            DB::commit();

            Toastr::success('Manual return has been recorded successfully!', 'Success');
            return response()->json([
                'success' => true,
                'message' => 'Manual return has been recorded successfully!',
                'redirect' => route('ViewAllManualProductReturns')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View all manual returns
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ManualProductReturn::with(['customer', 'return_items', 'creator'])
                ->orderBy('id', 'desc')
                ->get();

            return Datatables::of($data)
                ->editColumn('customer', function ($data) {
                    return $data->customer ? $data->customer->name : 'N/A';
                })
                ->editColumn('return_date', function ($data) {
                    return date("Y-m-d", strtotime($data->return_date));
                })
                ->editColumn('return_status', function ($data) {
                    $badges = [
                        'approved' => '<span class="badge badge-success">Approved</span>',
                        'pending' => '<span class="badge badge-warning">Pending</span>',
                        'rejected' => '<span class="badge badge-danger">Rejected</span>',
                    ];
                    $status = $badges[$data->return_status] ?? '<span class="badge badge-light">N/A</span>';
                    if ($data->status !== 'active') {
                        $status .= ' <span class="badge badge-secondary">Reversed</span>';
                    }
                    return $status;
                })
                ->addColumn('accounting_status', function ($data) {
                    if ($data->status !== 'active') {
                        return '<span class="badge badge-secondary">Reversed</span>';
                    }
                    if ((int) ($data->is_accounting_posted ?? 0) === 1) {
                        $ref = $data->accounting_reference ? ' title="Ref: ' . e($data->accounting_reference) . '"' : '';
                        return '<span class="badge badge-success"' . $ref . '>Posted</span>';
                    }
                    return '<span class="badge badge-warning">Not Posted</span>';
                })
                ->editColumn('total', function ($data) {
                    return '৳' . number_format($data->total, 2);
                })
                ->addIndexColumn()
                ->addColumn('action', function ($data) {
                    $btn = '<div class="dropdown">';
                    $btn .= '<button class="btn-sm btn-primary dropdown-toggle rounded" type="button" data-toggle="dropdown">';
                    $btn .= '<i class="fas fa-cog"></i> Actions';
                    $btn .= '</button>';
                    $btn .= '<div class="dropdown-menu">';
                    
                    // View
                    $btn .= '<a class="dropdown-item" href="' . route('ShowManualProductReturn', $data->slug) . '" target="_blank"><i class="fas fa-eye text-info"></i> View</a>';
                    
                    if ((int) ($data->is_accounting_posted ?? 0) !== 1 && $data->status === 'active') {
                        $btn .= '<a class="dropdown-item" href="' . route('EditManualProductReturn', $data->slug) . '"><i class="fas fa-edit text-warning"></i> Edit</a>';
                    }
                    
                    if ($data->status === 'active') {
                        $btn .= '<div class="dropdown-divider"></div>';
                        $btn .= '<a class="dropdown-item deleteBtn" href="javascript:void(0)" data-id="' . $data->slug . '"><i class="fas fa-undo text-danger"></i> Reverse</a>';
                    }
                    
                    $btn .= '</div></div>';
                    return $btn;
                })
                ->rawColumns(['return_status', 'accounting_status', 'action'])
                ->make(true);
        }
        return view('backend.manual_product_return.index');
    }

    /**
     * Show return details
     */
    public function show($slug)
    {
        $return = ManualProductReturn::with(['return_items', 'customer', 'creator'])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('backend.manual_product_return.show', compact('return'));
    }

    /**
     * Show edit form
     */
    public function edit($slug)
    {
        $return = ManualProductReturn::with(['return_items', 'customer'])->where('slug', $slug)->firstOrFail();
        
        return view('backend.manual_product_return.edit', compact('return'));
    }

    /**
     * Update return
     */
    public function update(Request $request, $slug)
    {
        $validator = Validator::make($request->all(), [
            'manual_product_return_id' => 'required|exists:manual_product_returns,id',
            'customer_id' => 'required|exists:customers,id',
            'return_date' => 'required|date',
            'return_items' => 'required|array|min:1',
            'return_items.*.product_id' => 'required|exists:products,id',
            'return_items.*.qty' => 'required|integer|min:1',
            'return_items.*.unit_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $return = ManualProductReturn::findOrFail($request->manual_product_return_id);
            if ((int) ($return->is_accounting_posted ?? 0) === 1) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Posted manual return cannot be edited. Reverse it and create a new return.',
                ], 422);
            }

            $oldTotal = $return->total;
            $customer = Customer::findOrFail($request->customer_id);
            $user = auth()->user();

            // Reverse previous wallet credit
            $customer->available_advance -= $oldTotal;

            // Reverse previous stock changes
            foreach ($return->return_items as $oldItem) {
                if ($oldItem->product_id) {
                    $product = Product::find($oldItem->product_id);
                    if ($product) {
                        $product->stock -= $oldItem->qty;
                        $product->save();
                    }
                }
            }

            // Delete old items
            ManualProductReturnItem::where('manual_product_return_id', $return->id)->delete();

            // Calculate new totals
            $subtotal = 0;
            foreach ($request->return_items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $unitPrice = (float) ($item['unit_price'] ?? ($product->discount_price ?: $product->price));
                $subtotal += (int) $item['qty'] * $unitPrice;
            }

            // Update return record
            $return->customer_id = $customer->id;
            $return->return_date = $request->return_date;
            $return->return_reason = $request->return_reason;
            $return->subtotal = $subtotal;
            $return->total = $subtotal;
            $return->note = $request->note;
            $return->updated_at = Carbon::now();
            $return->save();

            $random_no = random_int(100, 999) . random_int(1000, 9999);

            // Create new return items
            foreach ($request->return_items as $item) {
                $item_slug = Str::orderedUuid() . $random_no . $return->id . uniqid();
                $product = Product::findOrFail($item['product_id']);
                $unitPrice = (float) ($item['unit_price'] ?? ($product->discount_price ?: $product->price));
                
                ManualProductReturnItem::create([
                    'manual_product_return_id' => $return->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'qty' => $item['qty'],
                    'unit_price' => $unitPrice,
                    'total_price' => $item['qty'] * $unitPrice,
                    'slug' => $item_slug,
                    'creator' => $user->id,
                ]);

                $product->stock += $item['qty'];
                $product->save();

                insert_stock_log([
                    'warehouse_id' => null,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_return_id' => $return->id,
                    'quantity' => $item['qty'],
                    'type' => 'return',
                ]);
            }

            // Add new wallet credit
            $customer->available_advance += $subtotal;
            $customer->save();

            DB::commit();

            Toastr::success('Manual return has been updated successfully!', 'Success');
            return response()->json([
                'success' => true,
                'message' => 'Manual return has been updated successfully!',
                'redirect' => route('ViewAllManualProductReturns')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong! ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete return (soft delete)
     */
    public function destroy($slug)
    {
        try {
            DB::beginTransaction();

            $return = ManualProductReturn::with('return_items')->where('slug', $slug)->firstOrFail();

            app(ReturnRefundAccountingService::class)->reverseManualReturn($return);

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
     * Helper: Generate unique return code
     */
    private function generateReturnCode()
    {
        $year = Carbon::now()->format('y');
        $month = Carbon::now()->format('m');
        $prefix = 'MR' . $year . $month;

        $latestReturn = ManualProductReturn::where('return_code', 'like', $prefix . '%')
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

    /**
     * Helper: Record accounting entry
     */
    private function recordManualReturnAccounting($return, $customer, $amount)
    {
        try {
            // This would create accounting entries
            // Debit: Sales Return Account
            // Credit: Customer Wallet/Advance Account
            
            // Implementation depends on your accounting system structure
            // You can use the existing record_sales_accounting_return function
            // or create a dedicated manual return accounting function
            
            logger()->info('Manual return accounting recorded', [
                'return_id' => $return->id,
                'return_code' => $return->return_code,
                'customer_id' => $customer->id,
                'amount' => $amount
            ]);
            
            return true;
        } catch (\Exception $e) {
            logger()->error('Manual return accounting error: ' . $e->getMessage());
            return false;
        }
    }
}

