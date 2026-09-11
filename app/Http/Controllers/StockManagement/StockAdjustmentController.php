<?php

namespace App\Http\Controllers\StockManagement;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pos\DesktopPosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductPurchaseOrderProductUnit;
use App\Models\ProductStockLog;
use App\Models\ProductVariantCombination;
use Carbon\Carbon;

class StockAdjustmentController extends Controller
{
    /**
     * Display a listing of stock logs
     */
    public function index()
    {
        $logs = DB::table('product_stock_logs')
            ->leftJoin('products', 'product_stock_logs.product_id', '=', 'products.id')
            ->select(
                'product_stock_logs.*',
                'products.name as product_name',
                'products.code as product_code'
            )
            ->orderBy('product_stock_logs.created_at', 'desc')
            ->paginate(20);

        return view('backend.stock_management.index', compact('logs'));
    }

    /**
     * Show the form for creating a new stock adjustment
     */
    public function create()
    {
        return view('backend.stock_management.create');
    }

    /**
     * Search products for Ajax Select2
     */
    public function searchProducts(Request $request)
    {
        $desktop_controller = new DesktopPosController();
        $products = $desktop_controller->productsByCategory($request);
        return $products;
    }

    /**
     * Get product details with variants if applicable
     */
    public function getProductDetails($id)
    {
        try {
            $desktop_controller = new DesktopPosController();
            $products = $desktop_controller->productsByCategory(new Request(['q' => $id]));
            return $products;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Store a newly created stock adjustment
     * Steps:
     * 1. Insert log entry based on type
     * 2. Calculate closing stock from all logs
     * 3. Update product_stocks with calculated closing stock
     * 4. Update product total stock and availability_status
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:sales,purchase,return,initial,transfer,waste,manual add',
            'description' => 'nullable|string|max:1000',
            'quantity' => 'required_if:has_variants,false|numeric|min:0',
            'barcodes' => 'nullable|array',
            'barcodes.*' => 'string|max:255',
            'variants' => 'required_if:has_variants,true|array'
        ]);

        DB::beginTransaction();

        try {
            $product = Product::findOrFail($request->product_id);
            $hasVariants = $request->has_variants ?? false;
            $type = $request->type;
            $description = $request->description;
            $creator = Auth::id();
            $increment = ['purchase', 'initial', 'manual add', 'return'];

            if ($hasVariants && !empty($request->variants)) {
                // Handle variant stock adjustments
                foreach ($request->variants as $variantData) {
                    $combination_id = $variantData['combination_id'] ?? null;
                    $qty = $variantData['qty'];
                    $barcodes = $variantData['barcodes'] ?? [];
                    $variant_data = $variantData['attributes'];
                    $variant = ProductVariantCombination::find($combination_id);

                    if (in_array($type, $increment)) {
                        foreach ($barcodes as $barcode) {
                            $productPurchaseOrderProductUnit = new ProductPurchaseOrderProductUnit();
                            $productPurchaseOrderProductUnit->product_warehouse_id = null;
                            $productPurchaseOrderProductUnit->product_purchase_order_id = null;
                            $productPurchaseOrderProductUnit->product_purchase_order_product_id = null;
                            $productPurchaseOrderProductUnit->product_id = $product->id;
                            $productPurchaseOrderProductUnit->variant_combination_id = $combination_id ?? null;
                            $productPurchaseOrderProductUnit->code = $barcode;
                            $productPurchaseOrderProductUnit->price = $product->price;
                            $productPurchaseOrderProductUnit->unit_status = 'instock';
                            $productPurchaseOrderProductUnit->creator = auth()->user()->id;
                            $productPurchaseOrderProductUnit->slug = $product->id . time();
                            $productPurchaseOrderProductUnit->created_at = Carbon::now();
                            $productPurchaseOrderProductUnit->save();
                        }
                    } else {
                        $productPurchaseOrderProductUnits = ProductPurchaseOrderProductUnit::where('product_id', $product->id)->where('variant_combination_id', $combination_id)
                            ->where('unit_status', 'instock')
                            ->limit($qty)->get();
                        foreach ($productPurchaseOrderProductUnits as $productPurchaseOrderProductUnit) {
                            $productPurchaseOrderProductUnit->unit_status = $type == 'sales' ? 'sold' : $type;
                            $productPurchaseOrderProductUnit->save();
                        }
                    }

                    if ($variant && $qty > 0) {
                        ProductStockLog::insert([
                            'product_id' => $product->id,
                            'variant_combination_id' => $combination_id,
                            'has_variant' => 1,
                            'variant_combination_key' => $variant->combination_key,
                            'variant_sku' => $variant->sku,
                            'variant_data' => $variant->variant_values,
                            'product_name' => $product->name,
                            'quantity' => $qty,
                            'type' => $type,
                            'description' => $description,
                            'creator' => $creator,
                            'slug' => Str::slug($product->name . '-' . time()) . '-' . uniqid(),
                            'status' => 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $this->updateProductStockFromLogs($product->id, $variant->id);
                    }
                }
            } else {
                // Handle single product (non-variant) stock adjustment with barcodes
                $qty = (int) $request->quantity;
                $barcodes = $request->barcodes ?? [];

                if (in_array($type, $increment)) {
                    // Add stock: create one unit per barcode
                    foreach ($barcodes as $barcode) {
                        $barcode = is_string($barcode) ? trim($barcode) : '';
                        if ($barcode === '') {
                            continue;
                        }
                        $productPurchaseOrderProductUnit = new ProductPurchaseOrderProductUnit();
                        $productPurchaseOrderProductUnit->product_warehouse_id = null;
                        $productPurchaseOrderProductUnit->product_purchase_order_id = null;
                        $productPurchaseOrderProductUnit->product_purchase_order_product_id = null;
                        $productPurchaseOrderProductUnit->product_id = $product->id;
                        $productPurchaseOrderProductUnit->variant_combination_id = null;
                        $productPurchaseOrderProductUnit->code = $barcode;
                        $productPurchaseOrderProductUnit->price = $product->price;
                        $productPurchaseOrderProductUnit->unit_status = 'instock';
                        $productPurchaseOrderProductUnit->creator = auth()->user()->id;
                        $productPurchaseOrderProductUnit->slug = $product->id . time() . rand(1000, 9999);
                        $productPurchaseOrderProductUnit->created_at = Carbon::now();
                        $productPurchaseOrderProductUnit->save();
                    }
                } else {
                    // Reduce stock: mark existing instock units as sold/waste/transfer
                    $productPurchaseOrderProductUnits = ProductPurchaseOrderProductUnit::where('product_id', $product->id)
                        ->whereNull('variant_combination_id')
                        ->where('unit_status', 'instock')
                        ->limit($qty)
                        ->get();
                    foreach ($productPurchaseOrderProductUnits as $productPurchaseOrderProductUnit) {
                        $productPurchaseOrderProductUnit->unit_status = $type === 'sales' ? 'sold' : $type;
                        $productPurchaseOrderProductUnit->save();
                    }
                }

                if ($qty > 0) {
                    ProductStockLog::insert([
                        'product_id' => $product->id,
                        'variant_combination_id' => null,
                        'has_variant' => 0,
                        'variant_combination_key' => null,
                        'variant_sku' => null,
                        'variant_data' => null,
                        'product_name' => $product->name,
                        'quantity' => $qty,
                        'type' => $type,
                        'description' => $description,
                        'creator' => $creator,
                        'slug' => Str::slug($product->name . '-' . time()) . '-' . uniqid(),
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $this->updateProductStockFromLogs($product->id, null);
                }
            }

            // Step 4: Update product's total stock and availability_status
            $this->updateProductTotalStockAndAvailability($product->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment created successfully!',
                'redirect' => route('stock-adjustment.index')
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Error creating stock adjustment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate closing stock from logs and update product_stocks
     * This method calculates the closing stock based on ALL stock logs (not just adding/subtracting)
     */
    private function updateProductStockFromLogs($productId, $variantId = null)
    {
        try {
            // Calculate closing stock from logs based on event types
            // Stock IN: purchase, initial, manual add, return
            $stockIn = DB::table('product_stock_logs')
                ->where('product_id', $productId)
                ->when($variantId, function ($query) use ($variantId) {
                    return $query->where('variant_combination_id', $variantId);
                })
                ->whereIn('type', ['purchase', 'initial', 'manual add', 'return'])
                ->sum('quantity') ?? 0;

            // Stock OUT: sales, waste, transfer
            $stockOut = DB::table('product_stock_logs')
                ->where('product_id', $productId)
                ->when($variantId, function ($query) use ($variantId) {
                    return $query->where('variant_combination_id', $variantId);
                })
                ->whereIn('type', ['sales', 'waste', 'transfer'])
                ->sum('quantity') ?? 0;

            // Final closing stock
            $closingStock = $stockIn - $stockOut;

            // Find and update existing product_stocks entry (don't insert new)
            DB::table('product_stocks')
                ->where('product_id', $productId)
                ->when($variantId, function ($query) use ($variantId) {
                    return $query->where('variant_combination_id', $variantId);
                })
                ->update([
                    'qty' => max(0, $closingStock),
                    'date' => now()->format('Y-m-d'),
                    'updated_at' => now(),
                ]);
        } catch (\Exception $e) {
            Log::error('Error in updateProductStockFromLogs', [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update product's total stock and availability_status from product_stocks
     */
    private function updateProductTotalStockAndAvailability($productId)
    {
        try {
            $product = Product::find($productId);
            if (!$product) {
                return;
            }

            // Calculate total stock from product_stocks
            $totalStock = DB::table('product_stocks')
                ->where('product_id', $productId)
                ->where('status', 'active')
                ->sum('qty') ?? 0;

            // Set availability_status
            $availabilityStatus = ($totalStock > 0) ? 'in_stock' : 'out_stock';

            // Update product
            $product->stock = (int) $totalStock;
            $product->availability_status = $availabilityStatus;
            $product->save();
        } catch (\Exception $e) {
            Log::error('Error in updateProductTotalStockAndAvailability', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
