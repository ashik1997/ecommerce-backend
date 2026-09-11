<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Account\Models\AcAccount;
use App\Http\Controllers\Account\Models\AcTransaction;
use App\Http\Controllers\Account\Models\DbCustomerPayment;
use App\Http\Controllers\Account\Models\DbPaymentType;
use App\Http\Controllers\Courier\PathaoController;
use App\Http\Controllers\Courier\SteadfastController;
use App\Http\Controllers\Customer\Models\Customer;
use App\Http\Controllers\Customer\Models\CustomerContactPerson;
use App\Http\Controllers\Customer\Models\CustomerOpeningBalance;
use App\Http\Controllers\Inventory\Models\ProductWarehouse;
use App\Http\Controllers\Inventory\Models\ProductWarehouseRoom;
use App\Http\Controllers\Inventory\Models\ProductWarehouseRoomCartoon;
use App\Http\Controllers\Inventory\Models\ProductPurchaseOrderProduct;
use App\Models\AcEventMapping;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\Delivery\DeliveryProvider;
use App\Models\ProductOrderHold;
use App\Models\ProductOrderHoldItem;
use App\Models\ProductOrderProduct;
use App\Models\ProductOrderProductAllocation;
use App\Models\ProductPurchaseOrderProductUnit;
use App\Http\Controllers\Inventory\Models\ProductStock;
use App\Http\Controllers\Outlet\Models\CustomerSourceType;
use App\Http\Controllers\Outlet\Models\Outlet;
use App\Models\ProductVariantCombination;
use App\Models\BillingAddress;
use App\Models\ProductOrderCourierMethod;
use App\Models\ProductOrderDeliveryMethod;
use App\Models\ShippingInfo;
use App\Models\User;
use App\Models\UserSalesTarget;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\ProductOrderProductUnit;
use App\Models\ProductStockLog;
use App\Models\GeneralInfo;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Inventory\ProductOrderController as InventoryProductOrderController;
use App\Models\ProductOrderQuotation;
use App\Models\ProductOrderQuotationProduct;
use App\Models\OrderPayment;
use App\Models\OrderExtraChargeType;
use App\Models\ProductUnitPricing;
use App\Services\Customer\CustomerTransactionService;
use App\Services\Delivery\DeliveryShipmentSyncService;
use App\Services\FbMarketing\FbmOrderAttributionBridgeService;

class DesktopPosController extends Controller
{
    /**
     * Main POS page inside dashboard layout.
     */
    public function index()
    {
        $warehouses = ProductWarehouse::where('status', 'active')
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('backend.pos.desktop', compact('warehouses'));
    }

    /**
     * POS Desktop v3 — modular Vue 3 + Pinia shell (parallel to v1).
     */
    public function indexV3()
    {
        $warehouses = ProductWarehouse::where('status', 'active')
            ->orderBy('title')
            ->get(['id', 'title']);

        $commissionSalesUsers = DB::table('users')
            ->select('id', 'name', 'phone')
            ->where('status', 1)
            ->whereIn('user_type', [1, 2])
            ->orderBy('name')
            ->get();

        $commissionAffiliates = Schema::hasTable('affiliates')
            ? DB::table('affiliates')
                ->select('id', 'name', 'code', 'phone')
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
            : collect();

        return view('backend.pos.v3.index', compact('warehouses', 'commissionSalesUsers', 'commissionAffiliates'));
    }

    /**
     * Target stats (sales target analytics) for the authenticated user.
     * Returns totals for current month by default; optional from_date, to_date.
     */
    public function targetStats(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['success' => false, 'data' => null]);
        }

        $from = $request->get('from_date');
        $to = $request->get('to_date');
        $query = UserSalesTarget::where('user_id', $userId);

        if ($from) {
            $query->where('date', '>=', $from);
        }
        if ($to) {
            $query->where('date', '<=', $to);
        }
        if (!$from && !$to) {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $query->whereBetween('date', [$start, $end]);
        }

        $totalTargets = (clone $query)->sum('target');
        $totalCompleted = (clone $query)->sum('completed');
        $totalRemains = (clone $query)->sum('remains');
        $achievePercent = $totalTargets > 0 ? round(($totalCompleted / $totalTargets) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_targets' => (float) $totalTargets,
                'sales' => (float) $totalCompleted,
                'remains' => (float) $totalRemains,
                'achieve_percent' => $achievePercent,
            ],
        ]);
    }

    /**
     * Mobile-optimized POS layout.
     */
    public function mobile()
    {
        $warehouses = ProductWarehouse::where('status', 'active')
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('backend.pos.mobile', compact('warehouses'));
    }

    /**
     * Category data for filters.
     */
    public function categories()
    {
        $categories = DB::table('categories')
            ->select('id', 'name')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $subcategories = DB::table('subcategories')
            ->select('id', 'name', 'category_id')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        // Note: the table name in the schema is `child_categories`
        $childcategories = DB::table('child_categories')
            ->select('id', 'name', 'subcategory_id')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'subcategories' => $subcategories,
                'childcategories' => $childcategories,
            ],
        ]);
    }

    /**
     * Get all categories in nested form: category[subcategory[childcategory[]]]
     */
    public function nestedCategories()
    {
        // Get all active categories
        $categories = DB::table('categories')
            ->select('id', 'name', 'slug', 'icon')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        // Get all active subcategories
        $subcategories = DB::table('subcategories')
            ->select('id', 'name', 'slug', 'category_id', 'icon')
            ->where('status', 1)
            ->orderBy('name')
            ->get()
            ->groupBy('category_id');

        // Get all active child categories
        $childcategories = DB::table('child_categories')
            ->select('id', 'name', 'slug', 'category_id', 'subcategory_id', 'icon')
            ->where('status', 1)
            ->orderBy('name')
            ->get()
            ->groupBy('subcategory_id');

        // Build nested structure
        $nestedCategories = $categories->map(function ($category) use ($subcategories, $childcategories) {
            $categorySubcategories = $subcategories->get($category->id, collect())->map(function ($subcategory) use ($childcategories) {
                $subcategoryChildcategories = $childcategories->get($subcategory->id, collect())->map(function ($childcategory) {
                    return [
                        'id' => $childcategory->id,
                        'name' => $childcategory->name,
                        'slug' => $childcategory->slug,
                        'icon' => $childcategory->icon,
                    ];
                })->values();

                return [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'slug' => $subcategory->slug,
                    'icon' => $subcategory->icon,
                    'childcategories' => $subcategoryChildcategories,
                ];
            })->values();

            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'subcategories' => $categorySubcategories,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $nestedCategories,
        ]);
    }

    /**
     * Product search endpoint used by the live search bar.
     */
    public function searchProducts(Request $request)
    {
        return $this->productsByCategory($request);
    }

    /**
     * Products listing by category filters + pagination.
     */
    public function productsByCategory(Request $request)
    {
        $page = max(1, (int) $request->get('page', 1));
        $perPage = max(1, min(48, (int) $request->get('per_page', 5)));
        $warehouseId = $request->get('warehouse_id');

        $query = Product::query()
            ->where('products.status', 1)
            ->where('products.is_package', 0)
            ->with('unitPricing');

        $this->applyPosProductCatalogFilters($query, $request);

        if ($warehouseId !== null && $warehouseId > 0) {
            $stockSub = DB::table('product_stocks')
                ->selectRaw('product_id, COALESCE(SUM(qty), 0) as stock_qty')
                ->where('product_warehouse_id', $warehouseId)
                ->groupBy('product_id');
            $query->leftJoinSub($stockSub, 'warehouse_stock', 'products.id', '=', 'warehouse_stock.product_id')
                ->orderByRaw('COALESCE(warehouse_stock.stock_qty, 0) DESC')
                ->orderBy('products.name');
        } else {
            $query->orderBy('products.name');
        }

        $total = (clone $query)->distinct('products.id')->count('products.id');
        $items = $query->select('products.*')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function (Product $product) use ($warehouseId) {
                return $this->mapPosProductCatalogItem($product, $warehouseId);
            });

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'has_more' => ($page * $perPage) < $total,
                'page' => $page,
                'total' => $total,
                'per_page' => $perPage,
            ],
            'pagination' => ['more' => ($page * $perPage) < $total]
        ]);
    }

    /**
     * Shared POS product search/category filters.
     */
    protected function applyPosProductCatalogFilters($query, Request $request): void
    {
        $q = trim((string) $request->get('q', ''));

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('products.id', $q)
                    ->orWhere('products.code', $q)
                    ->orWhere('products.barcode', $q)
                    ->orWhere('products.sku', $q)
                    ->orWhere('products.name', 'like', '%' . $q . '%');
            });
        }

        if ($request->filled('childcategory_id')) {
            $query->where('products.childcategory_id', (int) $request->get('childcategory_id'));
            return;
        }

        if ($request->filled('subcategory_id')) {
            $subcategoryId = (int) $request->get('subcategory_id');
            $childcategoryIds = DB::table('child_categories')
                ->where('subcategory_id', $subcategoryId)
                ->where('status', 1)
                ->pluck('id');

            $query->where(function ($sub) use ($subcategoryId, $childcategoryIds) {
                $sub->where('products.subcategory_id', $subcategoryId);

                if ($childcategoryIds->isNotEmpty()) {
                    $sub->orWhereIn('products.childcategory_id', $childcategoryIds);
                }
            });

            return;
        }

        if (!$request->filled('category_id')) {
            return;
        }

        $categoryId = (int) $request->get('category_id');
        $subcategoryIds = DB::table('subcategories')
            ->where('category_id', $categoryId)
            ->where('status', 1)
            ->pluck('id');
        $childcategoryIds = $subcategoryIds->isEmpty()
            ? collect()
            : DB::table('child_categories')
                ->whereIn('subcategory_id', $subcategoryIds)
                ->where('status', 1)
                ->pluck('id');

        $query->where(function ($sub) use ($categoryId, $subcategoryIds, $childcategoryIds) {
            $sub->where('products.category_id', $categoryId);

            if ($subcategoryIds->isNotEmpty()) {
                $sub->orWhereIn('products.subcategory_id', $subcategoryIds);
            }

            if ($childcategoryIds->isNotEmpty()) {
                $sub->orWhereIn('products.childcategory_id', $childcategoryIds);
            }

            // Legacy rows that store comma-separated category ids.
            $sub->orWhereRaw('FIND_IN_SET(?, products.category_id)', [$categoryId]);
        });
    }

    /**
     * Normalize a product row for POS search/category responses.
     */
    protected function mapPosProductCatalogItem(Product $product, $warehouseId): array
    {
        $this->ensureProductBarcode($product);

        $stock_items = $this->getProductStockItemsForWarehouse($product, $warehouseId);
        $variants = ProductVariantCombination::where('product_id', $product->id)->where('status', 1)->get();

        $product_variants = [];
        $product_variant_combinations = [];

        foreach ($variants as $variant) {
            foreach ($variant->variant_values as $key => $value) {
                if (!isset($product_variants[$key])) {
                    $product_variants[$key] = [];
                }

                if (!in_array($value, $product_variants[$key], true)) {
                    $product_variants[$key][] = $value;
                }
            }

            $product_variant_combinations[] = [
                'id' => $variant->id,
                'price' => $variant->price ?? 0,
                'discount_price' => $variant->discount_price ?? 0,
                ...$variant->variant_values,
            ];
        }

        foreach ($product_variants as $key => $values) {
            $product_variants[$key] = array_values(array_unique($values));
        }

        $variant_values = [];
        $variant_stocks = [];

        if ($product->has_variant && $stock_items->count() > 0) {
            $variant_keys = [];
            $keys = [];

            foreach ($stock_items as $stock_item) {
                if ($stock_item->variant_combination_key) {
                    $variantKey = $stock_item->variant_combination_key;

                    if (!isset($variant_stocks[$variantKey])) {
                        $variant_stocks[$variantKey] = 0;
                    }

                    $variant_stocks[$variantKey] += (float) ($stock_item->qty ?? 0);
                }

                $variant_data = $stock_item->variant_data;

                if ($variant_data) {
                    $variant_keys[] = $variant_data;

                    foreach ($variant_data as $key => $value) {
                        $keys[] = $key;
                    }
                }
            }

            foreach (array_unique($keys) as $key) {
                $key_values = [];

                foreach ($variant_keys as $variant) {
                    if (isset($variant[$key])) {
                        $key_values[] = $variant[$key];
                    }
                }

                $variant_values[] = [$key => array_values(array_unique($key_values))];
            }
        }

        $final_price = + ($product->discount_price && $product->discount_price > 0
            ? $product->discount_price
            : $product->price);
        $stock = $this->getProductStockForWarehouse($product, $warehouseId);

        return [
            'id' => $product->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'text' => "{$product->name} ({$product->code}) - Stock: {$stock}",
            'title' => $product->name,
            'sku' => $product->sku ?? $product->code,
            'barcode' => $product->barcode,
            'has_variants' => (bool) $product->has_variant,
            'unit_price' => +$product->price,
            'main_price' => +$product->price,
            'wholesale_price' => +$product->wholesale_price,
            'retail_price' => +$product->retail_price,
            'mrp_price' => +$product->mrp_price,
            'final_price' => $final_price,
            'discount_price' => +$product->discount_price,
            'discount_parcent' => +$product->discount_parcent,
            'discount' => [
                'percent' => +$product->discount_parcent,
                'fixed' => $this->posProductCatalogDiscountFixed($product),
                'value' => $final_price,
            ],
            'stock' => $stock,
            'product_variants' => $product_variants,
            'product_variant_combinations' => $product_variant_combinations,
            'variant_values' => $variant_values,
            'variant_stocks' => $variant_stocks,
            'image_url' => $this->productImageUrl($product->image),
            'weight' => $product->weight ?? $product->shipping_info['weight'] ?? 0.5,
            'has_imei' => (bool) $product->has_imei,
            'prices' => $product_variant_combinations,
        ];
    }

    /**
     * Find product by barcode/code from product_purchase_order_product_units.
     * Returns product info with unit info attached.
     */
    public function productsByBarcode(Request $request)
    {
        $code = trim($request->get('code', ''));
        $warehouseId = $request->get('warehouse_id');

        if ($code === '') {
            return response()->json([
                'success' => false,
                'message' => 'Barcode/code is required.',
            ], 400);
        }

        // $unit = ProductPurchaseOrderProductUnit::where('code', $code)
        //     ->where('product_warehouse_id', $warehouseId)
        //     ->where('unit_status', 'instock')
        //     ->with(['product', 'variantCombination'])
        //     ->whereHas('product', function ($query) {
        //         $query->where('status', 1);
        //     })
        //     ->first();

        $baseQuery = ProductPurchaseOrderProductUnit::with(['product', 'variantCombination'])
            ->where('product_warehouse_id', $warehouseId)
            ->whereHas('product', fn ($query) => $query->where('status', 1));

        $unitProduct = (clone $baseQuery)
            ->where(function ($query) use ($code) {
                $query->where('code', $code)
                    ->orWhere('serial_no', $code)
                    ->orWhere('imei_1', $code)
                    ->orWhere('imei_2', $code);
            })
            ->where('unit_status', 'instock')
            ->orderBy('id')
            ->first();

        if (!$unitProduct) {
            $unitProduct = (clone $baseQuery)
                ->where(function ($query) use ($code) {
                    $query->where('code', $code)
                        ->orWhere('serial_no', $code)
                        ->orWhere('imei_1', $code)
                        ->orWhere('imei_2', $code);
                })
                ->orderBy('id')
                ->first();
        }

        if (!$unitProduct) {
            return response()->json([
                'success' => false,
                'message' => 'Product unit not found.',
            ], 404);
        }

        $unitQuery = (clone $baseQuery)
            ->where('product_id', $unitProduct->product_id)
            ->where('id', $unitProduct->id)
            ->where('unit_status', 'instock')
            ->orderBy('id');

        if ($unitProduct->variant_combination_id) {
            $unitQuery->where('variant_combination_id', $unitProduct->variant_combination_id);
        } else {
            $unitQuery->whereNull('variant_combination_id');
        }

        $unit = $unitQuery->first();

        if (!$unit) {
            return response()->json([
                'success' => false,
                'message' => 'product unavailable for this barcode.',
            ], 404);
        }

        $product = $unit->product;
        $variant = $unit->variantCombination;

        $warehouseName = null;
        $roomName = null;
        $cartoonName = null;
        $final_price = 0;

        if ($unit->product_purchase_order_product_id) {
            $orderProduct = ProductPurchaseOrderProduct::find($unit->product_purchase_order_product_id);
            if ($orderProduct) {
                if ($orderProduct->product_warehouse_id) {
                    $wh = ProductWarehouse::find($orderProduct->product_warehouse_id);
                    $warehouseName = $wh ? $wh->title : null;
                }
                if ($orderProduct->product_warehouse_room_id) {
                    $room = ProductWarehouseRoom::find($orderProduct->product_warehouse_room_id);
                    $roomName = $room ? $room->title : null;
                }
                if ($orderProduct->product_warehouse_room_cartoon_id) {
                    $cartoon = ProductWarehouseRoomCartoon::find($orderProduct->product_warehouse_room_cartoon_id);
                    $cartoonName = $cartoon ? $cartoon->title : null;
                }
            }
        }

        $purchasePrice = (float) ($unit->price ?? 0);
        $final_price = (float) ($product->discount_price && $product->discount_price > 0
            ? $product->discount_price
            : $product->price);

        $productData = [
            'id' => $product->id,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'image_url' => $this->productImageUrl($product->image),
            'unit' => [
                'id' => $unit->id,
                'code' => $unit->code,
                'purchase_price' => $purchasePrice,
                'price' => $product->price,
                'unit_status' => $unit->unit_status,
                'product_purchase_order_product_id' => $unit->product_purchase_order_product_id,
                'variant_combination_id' => $unit->variant_combination_id,
                'warehouse_name' => $warehouseName,
                'room_name' => $roomName,
                'cartoon_name' => $cartoonName,
                'serial_no' => $unit->serial_no,
                'imei_1' => $unit->imei_1,
                'imei_2' => $unit->imei_2,
                'supplier_warranty_start_date' => optional($unit->supplier_warranty_start_date)->format('Y-m-d'),
                'supplier_warranty_end_date' => optional($unit->supplier_warranty_end_date)->format('Y-m-d'),
                'customer_warranty_start_date' => optional($unit->customer_warranty_start_date)->format('Y-m-d'),
                'customer_warranty_end_date' => optional($unit->customer_warranty_end_date)->format('Y-m-d'),
                'warranty_note' => $unit->warranty_note,
            ],

            'unit_code' => $unit->code,
        ];

        // Product variants
        $variants = ProductVariantCombination::where('product_id', $product->id)
            ->where('status', 1)
            ->get();

        $product_variants = [];
        $product_variant_combinations = [];
        $variant_stocks = [];
        $variant_values = [];

        // all stock items
        $stock_items = $this->getProductStockItemsForWarehouse($product, $warehouseId);

        foreach ($variants as $variantItem) {

            foreach ($variantItem->variant_values as $key => $value) {

                if (!isset($product_variants[$key])) {
                    $product_variants[$key] = [];
                }

                if (!in_array($value, $product_variants[$key], true)) {
                    $product_variants[$key][] = $value;
                }
            }

            $product_variant_combinations[] = [
                'id' => $variantItem->id,
                'product_id' => $variantItem->product_id,
                'combination_key' => $variantItem->combination_key,
                'variant_name' => $variantItem->name,

                'price' => $variantItem->price ?? 0,
                'discount_price' => $variantItem->discount_price ?? 0,
                'sku' => $variantItem->sku,
                'barcode' => $variantItem->barcode,

                ...$variantItem->variant_values,
            ];
        }

        foreach ($product_variants as $key => $values) {
            $product_variants[$key] = array_values(array_unique($values));
        }

        // variant stock count
        if ($product->has_variant && $stock_items->count() > 0) {

            $variant_keys = [];
            $keys = [];

            foreach ($stock_items as $stock_item) {

                if ($stock_item->variant_combination_key) {
                    $variant_stocks[$stock_item->variant_combination_key]
                        = $stock_item->qty ?? 0;
                }

                $variant_data = $stock_item->variant_data;

                if ($variant_data) {

                    $variant_keys[] = $variant_data;

                    foreach ($variant_data as $key => $value) {
                        $keys[] = $key;
                    }
                }
            }

            $unique_keys = array_unique($keys);

            foreach ($unique_keys as $key) {

                $key_values = [];

                foreach ($variant_keys as $variantData) {

                    if (isset($variantData[$key])) {
                        $key_values[] = $variantData[$key];
                    }
                }

                $variant_values[] = [
                    $key => array_values(array_unique($key_values))
                ];
            }
        }

        if ($variant) {
            $variantMainPrice = (float) ($variant->getFinalPrice() ?? $product->price ?? 0);
            $variantDiscountPrice = (float) ($variant->getEffectiveDiscountPrice() ?? 0);
            $variantFinalPrice = $variantDiscountPrice > 0 ? $variantDiscountPrice : $variantMainPrice;

            $productData['variant_id'] = $variant->id;
            $productData['product_variant_id'] = $variant->id;
            $productData['variant_combination_key'] = $variant->combination_key;
            $productData['variant_name'] = $variant->name;
            $productData['variant_values'] = $variant->variant_values ?? [];
            $productData['variant_barcode'] = $variant->barcode ?? null;
            $productData['image_url'] = $this->productImageUrl($variant->image ?: $product->image);

            $productData['purchase_price'] = $purchasePrice;
            $productData['unit_price'] = $variantMainPrice;
            $productData['main_price'] = $variantMainPrice;
            $productData['final_price'] = $variantFinalPrice;
            $productData['discount_price'] = $variantDiscountPrice;
            $productData['discount'] = [
                'percent' => 0,
                'fixed' => max(0, $variantMainPrice - $variantFinalPrice),
                'value' => $variantFinalPrice,
            ];

            $productData['stock'] = $this->getVariantStockForWarehouse($variant, $warehouseId);
            $productData['max_qty'] = $productData['stock'];
        } else {
            $productData['variant_id'] = null;
            $productData['product_variant_id'] = null;
            $productData['variant_combination_key'] = null;
            $productData['variant_values'] = [];

            $productData['purchase_price'] = $purchasePrice;
            $productData['unit_price'] = $product->price;
            $productData['main_price'] = $product->price;
            $productData['final_price'] = $final_price;
            $productData['discount_price'] = $product->discount_price;
            $productData['discount'] = [
                'percent' => +$product->discount_parcent,
                'fixed' => $this->posProductCatalogDiscountFixed($product),
                'value' => $final_price,
            ];

            $productData['stock'] = $this->getProductStockForWarehouse($product, $warehouseId);
            $productData['max_qty'] = $productData['stock'];
        }

        $productData = [
            ...$productData,
            'unit_price' => (float) $productData['unit_price'],
            'main_price' => (float) $productData['main_price'],
            'final_price' => (float) $productData['final_price'],
            'discount_price' => (float) $productData['discount_price'],
            'discount_parcent' => +$product->discount_parcent,
            'wholesale_price' => +$product->wholesale_price,
            'retail_price' => +$product->retail_price,
            'mrp_price' => +$product->mrp_price,
            'discount' => $productData['discount'],
            'has_variants' => (bool) $product->has_variant,

            'product_variants' => $product_variants,
            'product_variant_combinations' => $product_variant_combinations,

            'variant_values' => $variant_values,
            'variant_stocks' => $variant_stocks,

            'prices' => $product_variant_combinations,
        ];

        return response()->json([
            'success' => true,
            'data' => $productData,
        ]);
    }

    /**
     * Catalog line discount in Tk (main price minus discount_price). Zero when no active discount.
     */
    protected function posProductCatalogDiscountFixed(Product $product): float
    {
        if (!$product->discount_price || (float) $product->discount_price <= 0) {
            return 0.0;
        }

        return max(0.0, (float) $product->price - (float) $product->discount_price);
    }

    /**
     * Per-unit sale price for a POS cart line: frontend sends this as item.discount_price (after line discount).
     */
    protected function posCartLineSalePrice(array $item, Product $product): float
    {
        $fromCart = data_get($item, 'discount_price');
        if ($fromCart !== null && $fromCart !== '' && is_numeric($fromCart)) {
            return (float) $fromCart;
        }

        return (float) ($product->discount_price ?: ($item['unit_price'] ?? 0));
    }

    protected function allocatePosSaleUnits(ProductOrder $order, ProductOrderProduct $orderProduct, Product $product, array $item, $warehouseId, $variantId = null): array
    {
        $qty = (float) ($item['qty'] ?? 0);
        $unitCount = (int) $qty;

        if ($qty <= 0 || abs($qty - $unitCount) > 0.0001) {
            throw new \Exception("Unit-tracked sale quantity must be a whole number for product {$product->id} - {$product->name}.");
        }

        $unitCode = $item['unit_code'] ?? null;
        $units = collect();

        if ($unitCode) {
            $exactUnitQuery = ProductPurchaseOrderProductUnit::where('code', $unitCode)
                ->where('product_id', $product->id)
                ->where('unit_status', 'instock')
                ->lockForUpdate();

            if ($warehouseId) {
                $exactUnitQuery->where('product_warehouse_id', $warehouseId);
            }
            if ($variantId) {
                $exactUnitQuery->where('variant_combination_id', $variantId);
            }

            $exactUnit = $exactUnitQuery->first();
            if (!$exactUnit) {
                throw new \Exception("Scanned unit {$unitCode} is not available for product {$product->id} - {$product->name}.");
            }

            $units->push($exactUnit);
        }

        $remaining = $unitCount - $units->count();
        if ($remaining > 0) {
            // Continue allocating the remaining required units from the next
            // available instock codes for this product, warehouse, and variant.
            $availableUnitsQuery = ProductPurchaseOrderProductUnit::where('product_id', $product->id)
                ->where('unit_status', 'instock')
                ->orderBy('id')
                ->lockForUpdate();

            if ($warehouseId) {
                $availableUnitsQuery->where('product_warehouse_id', $warehouseId);
            }
            if ($variantId) {
                $availableUnitsQuery->where('variant_combination_id', $variantId);
            }
            // if ($unitCode) {
            //     $availableUnitsQuery->where('code', '!=', $unitCode);
            // }

            $availableUnits = $availableUnitsQuery->limit($remaining)->get();
            foreach ($availableUnits as $availableUnit) {
                $units->push($availableUnit);
            }
        }

        if ($units->count() !== $unitCount) {
            throw new \Exception("Insufficient unit stock for product {$product->id} - {$product->name}. Required {$unitCount}, available {$units->count()}.");
        }

        $salePrice = $this->posCartLineSalePrice($item, $product);
        $firstUnit = null;
        $totalPurchase = 0;
        $totalProfit = 0;

        foreach ($units as $unit) {
            $purchaseOrderProduct = $unit->productPurchaseOrderProduct;
            $purchasePrice = (float) ($purchaseOrderProduct->purchase_price ?? $orderProduct->purchase_price ?? 0);
            $unitProfit = $salePrice - $purchasePrice;
            $totalPurchase += $purchasePrice;
            $totalProfit += $unitProfit;

            $unit->update([
                'sale_id' => $order->id,
                'unit_status' => 'sold',
                'updated_at' => Carbon::now(),
                'product_order_product_id' => $orderProduct->id,
                'customer_warranty_start_date' => $item['customer_warranty_start_date'] ?? Carbon::parse($order->sale_date ?? now())->format('Y-m-d'),
                'customer_warranty_end_date' => $item['customer_warranty_end_date'] ?? $unit->supplier_warranty_end_date,
                'warranty_note' => $item['warranty_note'] ?? $unit->warranty_note,
            ]);

            ProductOrderProductAllocation::create([
                'product_order_id' => $order->id,
                'product_order_product_id' => $orderProduct->id,
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'product_purchase_order_id' => $unit->product_purchase_order_id,
                'product_purchase_order_product_id' => $unit->product_purchase_order_product_id,
                'product_purchase_order_product_unit_id' => $unit->id,
                'product_warehouse_id' => $warehouseId,
                'qty' => 1,
                'purchase_price' => $purchasePrice,
                'sale_price' => $salePrice,
                'unit_profit' => $unitProfit,
                'net_profit' => $unitProfit,
                'cost_method' => 'fifo_unit',
                'meta' => ['unit_code' => $unit->code],
            ]);

            if (!$firstUnit) {
                $firstUnit = $unit;
            }
        }

        if ($firstUnit) {
            $firstPurchaseOrderProduct = $firstUnit->productPurchaseOrderProduct;
            $orderProduct->update([
                'product_purchase_order_id' => $firstUnit->product_purchase_order_id,
                'product_purchase_order_product_id' => $firstUnit->product_purchase_order_product_id,
                'product_purchase_order_product_unit_id' => $firstUnit->id,
                'product_warehouse_room_id' => $firstPurchaseOrderProduct->product_warehouse_room_id ?? null,
                'product_warehouse_room_cartoon_id' => $firstPurchaseOrderProduct->product_warehouse_room_cartoon_id ?? null,
                'product_supplier_id' => $firstPurchaseOrderProduct->product_supplier_id ?? null,
                'purchase_price' => $unitCount > 0 ? $totalPurchase / $unitCount : 0,
                'unit_profit' => $unitCount > 0 ? $totalProfit / $unitCount : 0,
                'net_profit' => $totalProfit,
            ]);
        }

        return [
            'first_unit' => $firstUnit,
            'total_purchase' => $totalPurchase,
            'total_profit' => $totalProfit,
        ];
    }

    protected function decrementPosProductStock(Product $product, $warehouseId, $variantId, float $qty): void
    {
        $remainingQty = $qty;

        $stocks = ProductStock::where('product_id', $product->id)
            ->where('product_warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->when($variantId, function ($query) use ($variantId) {
                $query->where('variant_combination_id', $variantId);
            })
            ->where('qty', '>', 0)
            ->orderBy('id', 'asc') // FIFO
            ->lockForUpdate()
            ->get();

        if ($stocks->sum('qty') < $qty) {
            throw new \Exception(
                "Insufficient stock balance for product {$product->id} - {$product->name}."
            );
        }

        foreach ($stocks as $stock) {

            if ($remainingQty <= 0) {
                break;
            }

            // current row theke koto kombe
            $deductQty = min($stock->qty, $remainingQty);

            $stock->decrement('qty', $deductQty);

            $remainingQty -= $deductQty;
        }
    }

    protected function updatePosOrderProfitSummary(ProductOrder $order): void
    {
        if (!Schema::hasColumn('product_orders', 'total_purchase_price')) {
            return;
        }

        $totalPurchase = (float) ProductOrderProduct::where('product_order_id', $order->id)
            ->sum(DB::raw('purchase_price * qty'));
        $grossProfit = (float) ProductOrderProduct::where('product_order_id', $order->id)
            ->sum('net_profit');

        $order->total_purchase_price = $totalPurchase;
        $order->gross_profit = $grossProfit;
        $order->net_profit = $grossProfit;
        $order->save();
    }

    protected function dispatchPosExternalActions(ProductOrder $order, array $deliveryInfo, bool $sendSms, $warehouseId, float $totalPaid): void
    {
        try {
            app(DeliveryShipmentSyncService::class)->syncFromProductOrder($order, $deliveryInfo, [
                'source_type' => $order->order_source ?? 'pos',
                'warehouse_id' => $warehouseId,
                'total_paid' => $totalPaid,
                'delivery_charge' => data_get($order->other_charges, 'delivery_charge', null),
                'status_source' => 'pos',
            ]);
        } catch (\Throwable $e) {
            Log::error('POS delivery shipment sync failed after order commit', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'message' => $e->getMessage(),
            ]);
        }

        if ($sendSms) {
            try {
                // Use the phone captured on this order first. The customer record can
                // be missing (for a walk-in sale) or may not contain the phone that
                // was entered/selected for this particular POS order.
                $customerPhone = trim((string) ($order->customer_phone ?: $order->customer?->phone));
                if ($customerPhone === '') {
                    Log::warning('POS SMS was requested but no customer phone was available', [
                        'order_id' => $order->id,
                        'order_code' => $order->order_code,
                    ]);
                } else {
                    $warehouse = ProductWarehouse::find($warehouseId);
                    $warehouseTitle = $warehouse->title ?? 'our store';
                    $message = "Thank you for shopping at {$warehouseTitle}. Invoice #{$order->order_code}. Paid: " . number_format($totalPaid, 2) . ". Thanks for shopping with us!";

                    $result = sms_send_single($customerPhone, $message, 'bulksmsbd');
                    if (!is_array($result) || empty($result['status'])) {
                        Log::error('POS SMS provider rejected the message', [
                            'order_id' => $order->id,
                            'order_code' => $order->order_code,
                            'provider_code' => is_array($result) ? ($result['code'] ?? null) : null,
                            'provider_message' => is_array($result) ? ($result['message'] ?? null) : 'No response from SMS provider',
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('POS SMS dispatch failed after order commit', [
                    'order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if (empty($deliveryInfo['courier_method'])) {
            return;
        }

        try {
            $courierTitle = strtolower($deliveryInfo['courier_method_title'] ?? '');

            if ($courierTitle === 'pathao') {
                (new PathaoController())->createOrder($order, $deliveryInfo['courier_method']);
            }

            if ($courierTitle === 'steadfast') {
                (new SteadfastController())->createOrder($order, $deliveryInfo['courier_method']);
            }
        } catch (\Throwable $e) {
            Log::error('POS courier dispatch failed after order commit', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'courier_method' => $deliveryInfo['courier_method'] ?? null,
                'courier_method_title' => $deliveryInfo['courier_method_title'] ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Pricing fields for POS cart (matches product list / productsByBarcode shape).
     */
    protected function posProductPricingPayload(Product $product): array
    {
        $final_price = + ($product->discount_price && $product->discount_price > 0
            ? $product->discount_price
            : $product->price);

        return [
            'main_price' => +$product->price,
            'wholesale_price' => +$product->wholesale_price,
            'retail_price' => +$product->retail_price,
            'mrp_price' => +$product->mrp_price,
            'final_price' => $final_price,
            'discount_price' => +$product->discount_price,
            'discount_parcent' => +$product->discount_parcent,
            'discount' => [
                'percent' => +$product->discount_parcent,
                'fixed' => $this->posProductCatalogDiscountFixed($product),
                'value' => $final_price,
            ],
        ];
    }

    /**
     * Barcode lookup with priority: variant barcode > product barcode.
     */
    public function barcodeLookup(Request $request)
    {
        $code = trim($request->get('code', ''));
        $warehouseId = $request->get('warehouse_id');

        if ($code === '') {
            return response()->json([
                'success' => false,
                'message' => 'Barcode is required.',
            ], 400);
        }

        // 1. Variant barcode
        $variant = ProductVariantCombination::where('barcode', $code)->first();
        if ($variant) {
            $product = $variant->product;
            $this->ensureVariantBarcode($variant);
            $pricing = $this->posProductPricingPayload($product);

            return response()->json([
                'success' => true,
                'data' => [
                    'single' => array_merge([
                        'id' => $variant->id,
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'title' => $variant->name,
                        'barcode' => $variant->barcode,
                        'unit_price' => $variant->getEffectiveDiscountPrice() ?? $variant->getFinalPrice(),
                        'stock' => $this->getVariantStockForWarehouse($variant, $warehouseId),
                        'image_url' => $this->productImageUrl($variant->image ?: $product->image),
                    ], $pricing),
                ],
            ]);
        }

        // 2. Product barcode
        $product = Product::where('barcode', $code)->first();
        if ($product) {
            $this->ensureProductBarcode($product);

            if ($product->has_variant) {
                $pricing = $this->posProductPricingPayload($product);
                $variants = $product->variantCombinations()->active()->get()->map(function (ProductVariantCombination $v) use ($product, $warehouseId, $pricing) {
                    $this->ensureVariantBarcode($v);

                    return array_merge([
                        'id' => $v->id,
                        'product_id' => $product->id,
                        'variant_id' => $v->id,
                        'title' => $v->name,
                        'barcode' => $v->barcode,
                        'unit_price' => $v->getEffectiveDiscountPrice() ?? $v->getFinalPrice(),
                        'stock' => $this->getVariantStockForWarehouse($v, $warehouseId),
                        'image_url' => $this->productImageUrl($v->image ?: $product->image),
                    ], $pricing);
                });

                return response()->json([
                    'success' => true,
                    'data' => [
                        'items' => $variants,
                    ],
                ]);
            }

            $pricing = $this->posProductPricingPayload($product);

            return response()->json([
                'success' => true,
                'data' => [
                    'single' => array_merge([
                        'id' => $product->id,
                        'product_id' => $product->id,
                        'variant_id' => null,
                        'title' => $product->name,
                        'barcode' => $product->barcode,
                        'unit_price' => $product->discount_price && $product->discount_price > 0
                            ? $product->discount_price
                            : $product->price,
                        'stock' => $this->getProductStockForWarehouse($product, $warehouseId),
                        'image_url' => $this->productImageUrl($product->image),
                    ], $pricing),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No product found for barcode.',
        ], 404);
    }

    /**
     * Optional server-side add-to-cart validation (stock check).
     */
    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variant_combinations,id',
            'qty' => 'required|numeric|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $variant = null;
        $warehouseId = $request->get('warehouse_id');
        $availableStock = $this->getProductStockForWarehouse($product, $warehouseId);

        if (!empty($validated['variant_id'])) {
            $variant = ProductVariantCombination::findOrFail($validated['variant_id']);
            $availableStock = $this->getVariantStockForWarehouse($variant, $warehouseId);
        }

        if ($availableStock < $validated['qty']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock available.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'ok' => true,
            ],
        ]);
    }

    /**
     * Save order as hold.
     */
    public function holdOrder(Request $request)
    {
        $payload = $request->validate([
            'cart' => 'required|array|min:1',
            'totals' => 'required|array',
            'customer.id' => 'nullable|integer',
            'order_note' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $warehouseId = $request->get('warehouse_id');

        $hold = ProductOrderHold::create([
            'user_id' => $user ? $user->id : null,
            'customer_id' => data_get($payload, 'customer.id'),
            'product_warehouse_id' => $warehouseId,
            'subtotal' => data_get($payload, 'totals.subtotal', 0),
            'discount_amount' => data_get($payload, 'totals.discount.amount', 0),
            'coupon_amount' => data_get($payload, 'totals.coupon.amount', 0),
            'extra_charge' => data_get($payload, 'totals.extra_charge', 0),
            'delivery_charge' => data_get($payload, 'totals.delivery_charge', 0),
            'round_off' => data_get($payload, 'totals.round_off', 0),
            'grand_total' => data_get($payload, 'totals.grand_total', 0),
            'meta' => [
                'cart' => $payload['cart'],
                'totals' => $payload['totals'],
                'customer' => $request->get('customer'),
                'note' => $payload['order_note'] ?? null,
            ],
        ]);

        foreach ($payload['cart'] as $item) {
            ProductOrderHoldItem::create([
                'hold_id' => $hold->id,
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'title' => $item['title'] ?? null,
                'qty' => $item['qty'] ?? 0,
                'unit_price' => $item['unit_price'] ?? 0,
                'discount_amount' => data_get($item, 'discount.amount', 0),
                'final_price' => $item['final_price'] ?? 0,
                'meta' => $item,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $hold->id,
            ],
            'message' => 'Order held successfully.',
        ]);
    }

    /**
     * Retrieve a hold order to resume.
     */
    public function getHold($id)
    {
        $hold = ProductOrderHold::with('items')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => $hold->meta['cart'] ?? [],
                'totals' => $hold->meta['totals'] ?? [],
                'customer' => $hold->meta['customer'] ?? null,
                'note' => $hold->meta['note'] ?? null,
                'hold' => $hold,
            ],
        ]);
    }

    /**
     * List holds for a warehouse (or all) for Hold List modal.
     */
    public function listHolds(Request $request)
    {
        $warehouseId = $request->get('warehouse_id');

        $query = ProductOrderHold::with('items')
            ->orderBy('id', 'desc');

        if ($warehouseId) {
            $query->where('product_warehouse_id', $warehouseId);
        }

        $holds = $query->limit(100)->get()->map(function (ProductOrderHold $hold) {
            return [
                'id' => $hold->id,
                'warehouse_id' => $hold->product_warehouse_id,
                'customer_id' => $hold->customer_id,
                'subtotal' => $hold->subtotal,
                'grand_total' => $hold->grand_total,
                'created_at' => optional($hold->created_at)->toDateTimeString(),
                'items_count' => $hold->items->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $holds,
        ]);
    }

    /**
     * Search customer by phone, name or email.
     */
    public function searchCustomer(Request $request)
    {
        $q = trim($request->get('q', ''));
        $phone = trim($request->get('phone', ''));
        $first = $request->get('first', false);
        $customerId = $request->get('customer_id', null);

        $customer_query = Customer::query()
            ->with(['contactPersons' => function ($query) {
                $query->where('status', 'active')->orderByDesc('is_primary')->orderBy('id');
            }])
            ->select('customers.*')
            ->selectRaw('(SELECT COUNT(*) FROM product_orders WHERE product_orders.customer_id = customers.id) as order_count');

        // If customer_id is provided, use it directly
        if ($customerId) {
            $customer_query->where('id', $customerId);
        } elseif ($q !== '') {
            // Otherwise use search query
            $customer_query->where(function ($sub) use ($q) {
                $sub->where('phone', 'like', '%' . $q . '%')
                    ->orWhere('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%')
                    ->orWhere('id', $q);
            });
        }

        $customer_query->where('status', 'active')
            ->orderBy('id', 'desc');

        if ($phone !== '') {
            $first = true;
            $customers = $customer_query->where('phone', $phone)->first();
        } else {
            $customers = $first ? $customer_query->first() : $customer_query->paginate(10);
        }

        if (!$first) {
            $customers->getCollection()->transform(function ($customer) {
                return $this->posCustomerPayload($customer);
            });
            $customers->appends(request()->all());
        } else if ($customers) {
            $customers = $this->posCustomerPayload($customers);
        }

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    protected function posCustomerPayload(Customer $customer): array
    {
        $customer->loadMissing(['contactPersons' => function ($query) {
            $query->where('status', 'active')->orderByDesc('is_primary')->orderBy('id');
        }]);

        $billingAddresses = BillingAddress::where('customer_id', $customer->id)
            ->where('order_id', 0)
            ->get()
            ->map(function ($addr) {
                return [
                    'full_name' => $addr->full_name,
                    'phone' => $addr->phone,
                    'address' => $addr->address,
                    'division_id' => $addr->division_id,
                    'district_id' => $addr->district_id,
                ];
            })
            ->toArray();

        $shippingAddresses = ShippingInfo::where('customer_id', $customer->id)
            ->where('order_id', 0)
            ->get()
            ->map(function ($addr) {
                return [
                    'full_name' => $addr->full_name,
                    'phone' => $addr->phone,
                    'address' => $addr->address,
                    'division_id' => $addr->division_id,
                    'district_id' => $addr->district_id,
                ];
            })
            ->toArray();

        $contactPersons = $customer->contactPersons->map(function ($contact) {
            return [
                'id' => $contact->id,
                'name' => $contact->name,
                'designation' => $contact->designation,
                'phone' => $contact->phone,
                'email' => $contact->email,
                'department' => $contact->department,
                'is_primary' => (bool) $contact->is_primary,
                'note' => $contact->note,
            ];
        })->values()->toArray();
        $primaryContact = collect($contactPersons)->firstWhere('is_primary', true) ?: ($contactPersons[0] ?? null);
        $orderDue = (float) ProductOrder::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->directCustomerReceivable()
            ->sum('due_amount');
        $oldDue = (float) CustomerOpeningBalance::where('customer_id', $customer->id)
            ->where('entry_type', 'due')
            ->where('status', 'active')
            ->sum('remaining_amount');
        $totalDue = $orderDue + $oldDue;

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'address' => $customer->address,
            'image' => $customer->image,
            'customer_source_type_id' => $customer->customer_source_type_id,
            'customer_type' => $customer->customer_type ?: 'person',
            'company_name' => $customer->company_name,
            'trade_name' => $customer->trade_name,
            'bin_no' => $customer->bin_no,
            'tin_no' => $customer->tin_no,
            'credit_limit' => (float) ($customer->credit_limit ?? 0),
            'payment_terms_days' => $customer->payment_terms_days,
            'allow_due' => (bool) ($customer->allow_due ?? true),
            'order_count' => (int) ($customer->order_count ?? ProductOrder::where('customer_id', $customer->id)->count()),
            'due_amount' => $totalDue,
            'due' => $totalDue,
            'order_due_amount' => $orderDue,
            'old_due_amount' => $oldDue,
            'available_advance' => (float) ($customer->available_advance ?? 0),
            'advance' => (float) ($customer->available_advance ?? 0),
            'billing_address' => $billingAddresses,
            'shipping_address' => $shippingAddresses,
            'contact_persons' => $contactPersons,
            'primary_contact' => $primaryContact,
            'selected_contact_person_id' => $primaryContact['id'] ?? null,
        ];
    }

    public function customerHistory(Customer $customer)
    {
        $customerId = (int) $customer->id;
        $perPage = min(50, max(10, (int) request('per_page', 20)));
        $ordersPage = max(1, (int) request('orders_page', 1));
        $refundsPage = max(1, (int) request('refunds_page', 1));

        $ordersQuery = ProductOrder::where('customer_id', $customerId)
            ->where('status', 'active')
            ->where('order_source', 'pos');

        $orderSummary = (clone $ordersQuery)
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COALESCE(SUM(total), 0) as total_purchase_amount')
            ->selectRaw('MAX(sale_date) as last_order_date')
            ->first();

        $purchases = DB::table('product_order_products')
            ->join('product_orders', 'product_orders.id', '=', 'product_order_products.product_order_id')
            ->where('product_orders.customer_id', $customerId)
            ->where('product_orders.status', 'active')
            ->where('product_orders.order_source', 'pos')
            ->where('product_order_products.status', 'active')
            ->select(
                'product_order_products.product_id',
                DB::raw('MAX(product_order_products.product_name) as product_name'),
                DB::raw('COALESCE(SUM(product_order_products.qty), 0) as qty_purchased'),
                DB::raw('COUNT(DISTINCT product_orders.id) as purchase_count'),
                DB::raw('COALESCE(SUM(product_order_products.total_price), 0) as total_amount'),
                DB::raw('MAX(product_orders.sale_date) as last_purchase_date')
            )
            ->groupBy('product_order_products.product_id')
            ->orderByDesc('last_purchase_date')
            ->get()
            ->map(function ($row) {
                return [
                    'product_id' => $row->product_id,
                    'product_name' => $row->product_name ?: 'N/A',
                    'qty_purchased' => (float) $row->qty_purchased,
                    'purchase_count' => (int) $row->purchase_count,
                    'total_amount' => (float) $row->total_amount,
                    'last_purchase_date' => $row->last_purchase_date,
                ];
            });

        $ordersPaginator = (clone $ordersQuery)
            ->select('id', 'order_code', 'sale_date', 'total', 'paid_amount', 'due_amount', 'slug')
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'orders_page', $ordersPage);

        $ordersPaginator->getCollection()->transform(function ($order) {
                return [
                    'id' => $order->id,
                    'order_code' => $order->order_code,
                    'sale_date' => $order->sale_date ? Carbon::parse($order->sale_date)->format('Y-m-d') : null,
                    'total' => (float) ($order->total ?? 0),
                    'paid_amount' => (float) ($order->paid_amount ?? 0),
                    'due_amount' => (float) ($order->due_amount ?? 0),
                    'slug' => $order->slug,
                ];
            });

        $returnSummary = Schema::hasTable('product_order_returns')
            ? DB::table('product_order_returns')
                ->join('product_orders', 'product_orders.id', '=', 'product_order_returns.product_order_id')
                ->where('product_order_returns.customer_id', $customerId)
                ->where('product_orders.order_source', 'pos')
                ->where('product_orders.status', 'active')
                ->where('product_order_returns.status', 'active')
                ->selectRaw('COUNT(*) as total_returns')
                ->selectRaw('COALESCE(SUM(product_order_returns.total), 0) as total_return_amount')
                ->selectRaw('MAX(product_order_returns.return_date) as last_return_date')
                ->first()
            : null;

        $returns = Schema::hasTable('product_order_returns') && Schema::hasTable('product_order_return_products')
            ? DB::table('product_order_return_products')
                ->join('product_order_returns', 'product_order_returns.id', '=', 'product_order_return_products.product_order_return_id')
                ->join('product_orders', 'product_orders.id', '=', 'product_order_returns.product_order_id')
                ->where('product_order_returns.customer_id', $customerId)
                ->where('product_orders.order_source', 'pos')
                ->where('product_orders.status', 'active')
                ->where('product_order_returns.status', 'active')
                ->where('product_order_return_products.status', 'active')
                ->select(
                    'product_order_return_products.product_id',
                    DB::raw('MAX(product_order_return_products.product_name) as product_name'),
                    DB::raw('COALESCE(SUM(product_order_return_products.qty), 0) as return_qty'),
                    DB::raw('COALESCE(SUM(product_order_return_products.total_price), 0) as return_amount'),
                    DB::raw('MAX(product_order_returns.return_date) as return_date'),
                    DB::raw('MAX(product_order_returns.return_code) as return_code'),
                    DB::raw('MAX(product_orders.order_code) as order_code')
                )
                ->groupBy('product_order_return_products.product_id')
                ->orderByDesc('return_date')
                ->get()
                ->map(function ($row) {
                    return [
                        'product_id' => $row->product_id,
                        'product_name' => $row->product_name ?: 'N/A',
                        'return_qty' => (float) $row->return_qty,
                        'return_amount' => (float) $row->return_amount,
                        'return_date' => $row->return_date,
                        'return_code' => $row->return_code,
                        'order_code' => $row->order_code,
                    ];
                })
            : collect();

        $refundSummary = Schema::hasTable('product_order_refunds')
            ? DB::table('product_order_refunds')
                ->join('product_orders', 'product_orders.id', '=', 'product_order_refunds.product_order_id')
                ->where('product_order_refunds.customer_id', $customerId)
                ->where('product_orders.order_source', 'pos')
                ->where('product_orders.status', 'active')
                ->where('product_order_refunds.status', 'active')
                ->selectRaw('COUNT(*) as total_refunds')
                ->selectRaw('COALESCE(SUM(product_order_refunds.refund_amount), 0) as total_refund_amount')
                ->selectRaw('MAX(product_order_refunds.refund_date) as last_refund_date')
                ->first()
            : null;

        $refundsPaginator = Schema::hasTable('product_order_refunds')
            ? DB::table('product_order_refunds')
                ->join('product_orders', 'product_orders.id', '=', 'product_order_refunds.product_order_id')
                ->leftJoin('product_order_returns', 'product_order_returns.id', '=', 'product_order_refunds.product_order_return_id')
                ->where('product_order_refunds.customer_id', $customerId)
                ->where('product_orders.order_source', 'pos')
                ->where('product_orders.status', 'active')
                ->where('product_order_refunds.status', 'active')
                ->select(
                    'product_order_refunds.id',
                    'product_order_refunds.refund_code',
                    'product_order_refunds.refund_date',
                    'product_order_refunds.refund_amount',
                    'product_order_refunds.refund_status',
                    'product_orders.order_code',
                    'product_order_returns.return_code'
                )
                ->orderByDesc('product_order_refunds.refund_date')
                ->orderByDesc('product_order_refunds.id')
                ->paginate($perPage, ['*'], 'refunds_page', $refundsPage)
            : null;

        if ($refundsPaginator) {
            $refundsPaginator->getCollection()->transform(function ($row) {
                return [
                    'id' => $row->id,
                    'refund_code' => $row->refund_code,
                    'refund_date' => $row->refund_date,
                    'refund_amount' => (float) $row->refund_amount,
                    'refund_status' => $row->refund_status,
                    'order_code' => $row->order_code,
                    'return_code' => $row->return_code,
                ];
            });
        }

        $services = Schema::hasTable('srms_service_instances') && Schema::hasTable('srms_services')
            ? DB::table('srms_service_instances')
                ->join('srms_services', 'srms_services.id', '=', 'srms_service_instances.service_id')
                ->where('srms_service_instances.customer_id', $customerId)
                ->whereIn('srms_service_instances.status', ['confirmed', 'billed', 'paid'])
                ->select(
                    'srms_services.id as service_id',
                    'srms_services.name as service_name',
                    'srms_services.type as service_type',
                    DB::raw('COUNT(srms_service_instances.id) as service_count'),
                    DB::raw('COALESCE(SUM(srms_service_instances.total_amount), 0) as total_amount'),
                    DB::raw('MAX(COALESCE(srms_service_instances.end_date, srms_service_instances.start_date, DATE(srms_service_instances.created_at))) as last_service_date')
                )
                ->groupBy('srms_services.id', 'srms_services.name', 'srms_services.type')
                ->orderByDesc('last_service_date')
                ->get()
                ->map(function ($row) {
                    return [
                        'service_id' => $row->service_id,
                        'service_name' => $row->service_name ?: 'N/A',
                        'service_type' => $row->service_type,
                        'service_count' => (int) $row->service_count,
                        'total_amount' => (float) $row->total_amount,
                        'last_service_date' => $row->last_service_date,
                    ];
                })
            : collect();

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                ],
                'summary' => [
                    'total_orders' => (int) ($orderSummary->total_orders ?? 0),
                    'total_purchase_amount' => (float) ($orderSummary->total_purchase_amount ?? 0),
                    'last_order_date' => $orderSummary->last_order_date ?? null,
                    'total_returns' => (int) ($returnSummary->total_returns ?? 0),
                    'total_return_amount' => (float) ($returnSummary->total_return_amount ?? 0),
                    'last_return_date' => $returnSummary->last_return_date ?? null,
                    'total_refunds' => (int) ($refundSummary->total_refunds ?? 0),
                    'total_refund_amount' => (float) ($refundSummary->total_refund_amount ?? 0),
                    'last_refund_date' => $refundSummary->last_refund_date ?? null,
                    'total_services' => $services->sum('service_count'),
                    'total_service_amount' => $services->sum('total_amount'),
                ],
                'purchases' => $purchases,
                'services' => $services,
                'orders' => $ordersPaginator->items(),
                'orders_pagination' => [
                    'current_page' => $ordersPaginator->currentPage(),
                    'last_page' => $ordersPaginator->lastPage(),
                    'per_page' => $ordersPaginator->perPage(),
                    'total' => $ordersPaginator->total(),
                ],
                'returns' => $returns,
                'refunds' => $refundsPaginator ? $refundsPaginator->items() : [],
                'refunds_pagination' => [
                    'current_page' => $refundsPaginator ? $refundsPaginator->currentPage() : 1,
                    'last_page' => $refundsPaginator ? $refundsPaginator->lastPage() : 1,
                    'per_page' => $refundsPaginator ? $refundsPaginator->perPage() : $perPage,
                    'total' => $refundsPaginator ? $refundsPaginator->total() : 0,
                ],
            ],
        ]);
    }

    protected function applyPosCustomerSnapshot($order, string $tableName, $customerId, array $customerPayload = []): void
    {
        if (!$customerId || (int) $customerId <= 1) {
            return;
        }

        $customer = Customer::with(['contactPersons' => function ($query) {
            $query->where('status', 'active')->orderByDesc('is_primary')->orderBy('id');
        }])->find($customerId);

        if (!$customer) {
            return;
        }

        $contactId = data_get($customerPayload, 'selected_contact_person_id')
            ?: data_get($customerPayload, 'contact_person_id');

        $contact = null;
        if ($contactId) {
            $contact = $customer->contactPersons->firstWhere('id', (int) $contactId);
        }
        if (!$contact) {
            $contact = $customer->contactPersons->firstWhere('is_primary', true)
                ?: $customer->contactPersons->first();
        }

        if (Schema::hasColumn($tableName, 'customer_contact_person_id')) {
            $order->customer_contact_person_id = $contact?->id;
        }
        if (Schema::hasColumn($tableName, 'customer_type_snapshot')) {
            $order->customer_type_snapshot = $customer->customer_type ?: 'person';
        }
        if (Schema::hasColumn($tableName, 'company_name_snapshot')) {
            $order->company_name_snapshot = $customer->company_name ?: null;
        }
        if (Schema::hasColumn($tableName, 'contact_person_snapshot')) {
            $order->contact_person_snapshot = $contact ? [
                'id' => $contact->id,
                'name' => $contact->name,
                'designation' => $contact->designation,
                'phone' => $contact->phone,
                'email' => $contact->email,
                'department' => $contact->department,
            ] : null;
        }
    }

    /**
     * Create or update a customer from POS.
     */
    public function createCustomer(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:customers,id',
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:60',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'customer_type' => 'nullable|string|in:person,company',
            'company_name' => 'nullable|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'bin_no' => 'nullable|string|max:100',
            'tin_no' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0',
            'allow_due' => 'nullable|boolean',
            'save_as_user' => 'nullable|boolean',
            'password' => 'nullable|string|min:6|required_if:save_as_user,1',
            'billing_address' => 'nullable|array',
            'billing_address.*.full_name' => 'nullable|string|max:255',
            'billing_address.*.phone' => 'nullable|string|max:60',
            'billing_address.*.address' => 'nullable|string',
            'shipping_address' => 'nullable|array',
            'shipping_address.*.full_name' => 'nullable|string|max:255',
            'shipping_address.*.phone' => 'nullable|string|max:60',
            'shipping_address.*.address' => 'nullable|string',
            'customer_source_type_id' => 'nullable|integer|exists:customer_source_types,id',
            'contact_persons' => 'nullable|array',
            'contact_persons.*.id' => 'nullable|integer|exists:customer_contact_persons,id',
            'contact_persons.*.name' => 'nullable|string|max:255',
            'contact_persons.*.designation' => 'nullable|string|max:255',
            'contact_persons.*.phone' => 'nullable|string|max:60',
            'contact_persons.*.email' => 'nullable|email|max:100',
            'contact_persons.*.department' => 'nullable|string|max:255',
            'contact_persons.*.is_primary' => 'nullable|boolean',
            'contact_persons.*.note' => 'nullable|string',
        ]);

        $customer = DB::transaction(function () use ($data, $request) {
            if (!empty($data['id'])) {
                $customer = Customer::find($data['id']) ?? new Customer();
            } elseif (!empty($data['mobile'])) {
                $customer = Customer::where('phone', $data['mobile'])->first() ?? new Customer();
            } else {
                $customer = new Customer();
            }

            $customer->name = $data['name'];
            $customer->phone = $data['mobile'];
            $customer->email = $data['email'] ?? null;
            $customer->address = $data['address'] ?? null;
            $customer->customer_source_type_id = $data['customer_source_type_id'] ?? null;
            $customer->customer_type = $data['customer_type'] ?? $customer->customer_type ?? 'person';
            $customer->company_name = $data['company_name'] ?? null;
            $customer->trade_name = $data['trade_name'] ?? null;
            $customer->bin_no = $data['bin_no'] ?? null;
            $customer->tin_no = $data['tin_no'] ?? null;
            $customer->credit_limit = (float) ($data['credit_limit'] ?? $customer->credit_limit ?? 0);
            $customer->payment_terms_days = $data['payment_terms_days'] ?? null;
            $customer->allow_due = array_key_exists('allow_due', $data) ? (bool) $data['allow_due'] : true;

            if (!$customer->exists) {
                $customer->creator = Auth::id();
                $customer->slug = Str::orderedUuid();
                $customer->status = 'active';
            }
            $customer->save();

            if ($request->has('billing_address')) {
                BillingAddress::where('customer_id', $customer->id)
                    ->where('order_id', 0)
                    ->delete();

                $billingAddresses = array_filter($data['billing_address'] ?? [], function ($addr) {
                    return !empty($addr['full_name']) || !empty($addr['address']);
                });

                foreach ($billingAddresses as $addr) {
                    BillingAddress::create([
                        'customer_id' => $customer->id,
                        'order_id' => 0,
                        'full_name' => $addr['full_name'] ?? null,
                        'phone' => $addr['phone'] ?? null,
                        'address' => $addr['address'] ?? null,
                        'division_id' => $addr['division_id'] ?? null,
                        'district_id' => $addr['district_id'] ?? null,
                        'city' => null,
                        'country' => null,
                        'thana' => null,
                        'post_code' => null,
                    ]);
                }
            }

            if ($request->has('shipping_address')) {
                ShippingInfo::where('customer_id', $customer->id)
                    ->where('order_id', 0)
                    ->delete();

                $shippingAddresses = array_filter($data['shipping_address'] ?? [], function ($addr) {
                    return !empty($addr['full_name']) || !empty($addr['address']);
                });

                foreach ($shippingAddresses as $addr) {
                    ShippingInfo::create([
                        'customer_id' => $customer->id,
                        'order_id' => 0,
                        'full_name' => $addr['full_name'] ?? null,
                        'phone' => $addr['phone'] ?? null,
                        'address' => $addr['address'] ?? null,
                        'division_id' => $addr['division_id'] ?? null,
                        'district_id' => $addr['district_id'] ?? null,
                        'email' => null,
                        'gender' => null,
                        'city' => null,
                        'country' => null,
                        'thana' => null,
                        'post_code' => null,
                    ]);
                }
            }

            if ($request->has('contact_persons')) {
                $this->syncCustomerContactPersons($customer, $data['contact_persons'] ?? []);
            }

            if (!empty($data['save_as_user']) && ($data['save_as_user'] == 1 || $data['save_as_user'] === true) && !empty($data['password'])) {
                $existingUser = User::where(function ($query) use ($data) {
                    if (!empty($data['email'])) {
                        $query->where('email', $data['email']);
                    }
                    $query->orWhere('phone', $data['mobile']);
                })->first();

                if (!$existingUser) {
                    $user = User::create([
                        'store_id' => Auth::user()->store_id ?? null,
                        'name' => $data['name'],
                        'phone' => $data['mobile'],
                        'email' => $data['email'] ?? null,
                        'password' => Hash::make($data['password']),
                        'address' => $data['address'] ?? null,
                        'balance' => 0,
                        'user_type' => 3,
                        'status' => 1,
                    ]);

                    $customer->user_id = $user->id;
                    $customer->save();
                }
            }

            return $customer->fresh(['contactPersons']);
        });

        return response()->json([
            'success' => true,
            'data' => $this->posCustomerPayload($customer),
            'message' => 'Customer saved successfully.',
        ]);
    }

    protected function syncCustomerContactPersons(Customer $customer, array $contacts): void
    {
        $keptIds = [];
        $hasPrimary = false;

        foreach ($contacts as $contact) {
            if (empty($contact['name']) && empty($contact['phone']) && empty($contact['email'])) {
                continue;
            }

            $isPrimary = !$hasPrimary && !empty($contact['is_primary']);
            $hasPrimary = $hasPrimary || $isPrimary;

            $model = !empty($contact['id'])
                ? CustomerContactPerson::where('customer_id', $customer->id)->find($contact['id'])
                : null;

            if (!$model) {
                $model = new CustomerContactPerson();
                $model->customer_id = $customer->id;
            }

            $model->name = $contact['name'] ?? null;
            $model->designation = $contact['designation'] ?? null;
            $model->phone = $contact['phone'] ?? null;
            $model->email = $contact['email'] ?? null;
            $model->department = $contact['department'] ?? null;
            $model->is_primary = $isPrimary;
            $model->note = $contact['note'] ?? null;
            $model->status = 'active';
            $model->save();

            $keptIds[] = $model->id;
        }

        if (!$hasPrimary && !empty($keptIds)) {
            CustomerContactPerson::where('customer_id', $customer->id)
                ->where('id', $keptIds[0])
                ->update(['is_primary' => true]);
        }

        CustomerContactPerson::where('customer_id', $customer->id)
            ->when(!empty($keptIds), function ($query) use ($keptIds) {
                $query->whereNotIn('id', $keptIds);
            })
            ->update(['status' => 'inactive']);
    }

    public function deleteCustomer(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:customers,id',
        ]);

        $customer = Customer::findOrFail($data['id']);

        // Check if customer has any orders
        $orderCount = ProductOrder::where('customer_id', $customer->id)->count();

        if ($orderCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete customer. Customer has ' . $orderCount . ' order(s). Please remove orders first.',
            ], 422);
        }

        // Delete related billing addresses
        BillingAddress::where('customer_id', $customer->id)
            ->where('order_id', 0)
            ->delete();

        // Delete related shipping addresses
        ShippingInfo::where('customer_id', $customer->id)
            ->where('order_id', 0)
            ->delete();

        // Delete the customer
        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully.',
        ]);
    }

    /**
     * Apply coupon using existing promo_codes logic.
     */
    public function applyCoupon(Request $request)
    {
        $code = trim($request->get('code', ''));
        $subtotal = (float) $request->get('subtotal', 0);

        if ($code === '') {
            return response()->json([
                'success' => false,
                'message' => 'Coupon code is required.',
            ], 400);
        }

        $coupon = DB::table('promo_codes')->where('code', $code)->where('status', 1)->first();
        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found.',
            ], 404);
        }

        $today = date('Y-m-d');
        if ($coupon->effective_date && $coupon->effective_date > $today) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon is not applicable.',
            ], 422);
        }
        if ($coupon->expire_date && $coupon->expire_date < $today) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon is expired.',
            ], 422);
        }

        if ($coupon->minimum_order_amount && $coupon->minimum_order_amount > $subtotal) {
            return response()->json([
                'success' => false,
                'message' => 'Order amount is below coupon minimum.',
            ], 422);
        }

        $discount = 0.0;
        $type = $coupon->type === 2 ? 'percent' : 'fixed';

        if ($type === 'percent') {
            $discount = ($subtotal * $coupon->value) / 100;
        } else {
            $discount = $coupon->value;
        }

        if ($discount > $subtotal) {
            return response()->json([
                'success' => false,
                'message' => 'Discount cannot exceed subtotal.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'value' => (float) $coupon->value,
                'amount' => $discount,
            ],
            'message' => 'Coupon applied.',
        ]);
    }

    public function extraChargeTypes(Request $request)
    {
        $q = trim((string) $request->get('q', $request->get('term', '')));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = max(1, min(25, (int) $request->get('per_page', 15)));

        $query = OrderExtraChargeType::query()
            ->where('status', 'active')
            ->whereIn('scope', ['sales', 'pos', 'all'])
            ->orderBy('title');

        if ($q !== '') {
            $query->where('title', 'like', '%' . $q . '%');
        }

        $total = (clone $query)->count();
        $items = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function (OrderExtraChargeType $type) {
                return [
                    'id' => $type->id,
                    'title' => $type->title,
                    'text' => $type->title,
                    'default_amount' => (float) $type->default_amount,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'more' => ($page * $perPage) < $total,
            ],
        ]);
    }

    public function storeExtraChargeType(Request $request)
    {
        $payload = $request->validate([
            'title' => 'required|string|max:255',
            'default_amount' => 'nullable|numeric|min:0',
        ]);

        $type = OrderExtraChargeType::create([
            'title' => trim($payload['title']),
            'default_amount' => (float) ($payload['default_amount'] ?? 0),
            'scope' => 'sales',
            'creator' => Auth::id(),
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $type->id,
                'title' => $type->title,
                'text' => $type->title,
                'default_amount' => (float) $type->default_amount,
            ],
            'message' => 'Extra charge type created.',
        ]);
    }

    /**
     * Optional totals calculation endpoint (authoritative).
     */
    public function calculateTotals(Request $request)
    {
        $cart = $request->get('cart', []);
        $extraChargeLines = $this->normalizePosExtraChargeLines($request->get('extra_charge_lines', []));
        $extra = count($extraChargeLines) > 0
            ? array_sum(array_column($extraChargeLines, 'amount'))
            : (float) $request->get('extra_charge', 0);
        $delivery = (float) $request->get('delivery_charge', 0);
        $roundOff = (float) $request->get('round_off', 0);

        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += (float) ($item['final_price'] ?? 0);
        }

        $discountAmount = (float) data_get($request->get('discount', []), 'amount', 0);
        $couponAmount = (float) data_get($request->get('coupon', []), 'amount', 0);

        $grand = $subtotal - $discountAmount - $couponAmount + $extra + $delivery - $roundOff;

        return response()->json([
            'success' => true,
            'data' => [
                'subtotal' => $subtotal,
                'discount' => ['amount' => $discountAmount],
                'coupon' => ['amount' => $couponAmount],
                'extra_charge' => $extra,
                'extra_charge_lines' => $extraChargeLines,
                'delivery_charge' => $delivery,
                'round_off' => $roundOff,
                'grand_total' => $grand,
            ],
        ]);
    }

    /**
     * Create final ProductOrder from POS payload.
     */
    public function createOrder(Request $request)
    {
        // return response()->json($request->all(), 500);
        $payload = $request->validate([
            'cart' => 'required|array|min:1',
            'totals' => 'required|array',
            'customer.id' => 'nullable|integer|exists:customers,id',
            'customer.name' => 'nullable|string|max:255',
            // 'payments' => 'required|array|min:0',
            'payments' => 'nullable|array',
            'payments.*.amount' => 'nullable|numeric|min:0',
            'payments.*.payment_type_id' => 'required_with:payments.*.amount|nullable|integer|exists:db_paymenttypes,id',
            'use_advance' => 'boolean',
            'advance_amount' => 'nullable|numeric|min:0',
            'order_note' => 'nullable|string|max:500',
            'order_source' => 'nullable|string|max:50',
            'salesman_id' => 'nullable|integer|exists:users,id',
            'affiliate_code' => 'nullable|string|max:80',
            'sms_send_to_customer' => 'nullable|boolean',
        ]);

        $cart = $payload['cart'];
        $totals = $payload['totals'];
        $paymentLines = $request->get('payments', []) ?? [];
        $useAdvance = (bool) ($payload['use_advance'] ?? false);
        $warehouseId = $request->get('warehouse_id');
        $deliveryInfo = $request->get('delivery_info', []) ?? [];
        $orderStatus = $request->get('order_status', 'quotation');

        $customerAddress = request()->get('customer')['address'] ?? null;
        $customerPhone = request()->get('customer')['phone'] ?? null;
        $customerName = request()->get('customer')['name'] ?? null;

        // $grandTotal = (float) data_get($totals, 'grand_total', 0);
        $serverTotals = $this->calculateOrderTotalsFromCart($cart, $totals);

        $totals = $serverTotals;
        $grandTotal = $serverTotals['grand_total'];
        $cart = $serverTotals['cart'];

        $paymentTotal = 0;
        foreach ($paymentLines as $line) {
            if (!empty($line['amount'])) {
                $paymentTotal += (float) $line['amount'];
            }
        }

        $customerId = data_get($payload, 'customer.id');
        $advanceUsed = 0;
        $customer = null;

        $is_confirmed = in_array($orderStatus, ['invoiced', 'delivered']);

        if ($customerId && $customerId > 1) {
            $customer = Customer::findOrFail($customerId);
            if ($customerAddress) {
                $customer->address = $customerAddress;
                $customer->save();
            }
            if ($useAdvance) {
                // Use provided advance_amount if available, otherwise calculate automatically
                $providedAdvanceAmount = data_get($payload, 'advance_amount', 0);
                if ($providedAdvanceAmount > 0) {
                    // Validate that provided amount doesn't exceed available advance
                    $advanceUsed = min($providedAdvanceAmount, $customer->available_advance, max(0, $grandTotal - $paymentTotal));
                } else {
                    // Auto-calculate if not provided
                    if ($customer->available_advance > 0) {
                        $advanceUsed = min($customer->available_advance, max(0, $grandTotal - $paymentTotal));
                    }
                }
            }
        }

        $totalPaid = $paymentTotal + $advanceUsed;

        /** create quotation */
        if (!$is_confirmed) {
            return $this->createQuotation(
                warehouseId: $warehouseId,
                customerId: $customerId,
                customerPhone: $customerPhone,
                customerName: $customerName,
                orderStatus: $orderStatus,
                grandTotal: $grandTotal,
                totalPaid: $totalPaid,
                customerAddress: $customerAddress,
                totals: $totals,
                paymentLines: $paymentLines,
                advanceUsed: $advanceUsed,
                cart: $cart,
                deliveryInfo: $deliveryInfo,
                orderSource: $payload['order_source'] ?? 'pos',
                customerPayload: data_get($payload, 'customer', [])
            );
        }

        // Check if walking customer (id == 1) cannot have due amount
        if ($customerId == 1 && abs($totalPaid - $grandTotal) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Walking customer orders must be fully paid. Due amounts are not allowed for walk-in customers.',
            ], 422);
        }

        $newDueAmount = max(0, $grandTotal - $totalPaid);
        if ($customer && $newDueAmount > 0) {
            if (!$customer->allow_due) {
                return response()->json([
                    'success' => false,
                    'message' => 'Due sale is disabled for this customer.',
                ], 422);
            }

            $currentDue = (float) ProductOrder::where('customer_id', $customer->id)->directCustomerReceivable()->sum('due_amount');
            $creditLimit = (float) ($customer->credit_limit ?? 0);
            if ($creditLimit > 0 && ($currentDue + $newDueAmount) > $creditLimit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer credit limit exceeded.',
                ], 422);
            }
        }

        // POS orders must be fully paid; no due allowed
        // if (abs($totalPaid - $grandTotal) > 0.01) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'POS orders must be fully paid. Total payment (including advance) must equal grand total.',
        //     ], 422);
        // }

        DB::beginTransaction();
        try {
            $order = new ProductOrder();
            $order->store_id = auth()->user()->store_id;
            $order->order_code = $this->generateOrderCode();
            $order->product_warehouse_id = $warehouseId;
            $order->product_order_quotation_id = $request->get('quotation_id');
            $order->customer_id = $customerId;
            $order->customer_phone = $customerPhone;
            $order->customer_name = $customerName;
            $this->applyPosCustomerSnapshot($order, 'product_orders', $customerId, data_get($payload, 'customer', []));
            $order->sale_date = Carbon::now();
            $order->subtotal = data_get($totals, 'subtotal', 0);
            $order->other_charges = $this->buildPosOtherChargesPayload($totals);
            $order->other_charge_amount = ((float) data_get($totals, 'extra_charge', 0)) + ((float) data_get($totals, 'delivery_charge', 0));
            $order->discount_type = data_get($totals, 'discount.type', null);
            $order->discount_amount = data_get($totals, 'discount.value', 0);
            $order->calculated_discount_amount = data_get($totals, 'discount.amount', 0);
            $order->round_off_from_total = data_get($totals, 'round_off', 0);
            $order->decimal_round_off = data_get($totals, 'round_off', 0);
            $order->total = $grandTotal;
            $order->paid_amount = $totalPaid;
            $order->due_amount = max(0, $grandTotal - $totalPaid);
            $order->address = $customerAddress;
            $order->payments = array_merge(
                $this->paymentsArrayFromLines($paymentLines),
                [
                    'advance_used' => $advanceUsed,
                    'total_paid' => $totalPaid,
                    'total_due' => max(0, $grandTotal - $totalPaid),
                ]
            );
            $order->note = $deliveryInfo['order_note'] ?? null;
            $order->order_source = $request->get('quotation_id') > 0 
            ? 'pos' 
            : ($payload['order_source'] ?? 'pos');
            $order->order_status = $orderStatus;
            $order->delivery_info = $deliveryInfo;
            $order->creator = Auth::id();
            $order->salesman_id = $request->get('salesman_id') ?: Auth::id();
            $order->affiliate_code = $request->get('affiliate_code') ?: data_get($payload, 'affiliate_code');
            if ($order->affiliate_code) {
                $affiliate = \App\Models\Affiliate::where('code', $order->affiliate_code)->where('status', 'active')->first();
                if ($affiliate) {
                    $order->affiliate_id = $affiliate->id;
                }
            }
            $order->status = 'active';
            $order->created_at = Carbon::now();
            $order->save();

            foreach ($cart as $item) {
                $product = Product::findOrFail($item['product_id']);
                $variantId = $item['variant_id'] ?? null;
                $purchase_price = DB::table('product_purchase_order_products')
                ->where('product_id', $item['product_id'])
                ->orderBy('id', 'desc')
                ->value('purchase_price') ?? 0;
                $price = $this->posCartLineSalePrice($item, $product);
                $unit_profit = ( $price - $purchase_price );
                $net_profit = ( $unit_profit * $item['qty'] );

                $productOrderProduct = ProductOrderProduct::create([
                    'product_warehouse_id' => $warehouseId,
                    'product_warehouse_room_id' => null,
                    'product_warehouse_room_cartoon_id' => null,
                    'product_supplier_id' => null,
                    'product_order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variantId,
                    'unit_price_id' => null,
                    'product_name' => $product->name,
                    'product_note' => $item['product_note'] ?? '',
                    'qty' => $item['qty'],
                    'product_price' =>  $item['unit_price'],
                    'product_image' => $product->image,
                    'discount_type' => data_get($item, 'discount.type', 'in_percentage'),
                    // 'discount_amount' => data_get($item, 'discount.value', 0),
                    'discount_amount' => data_get($item, 'discount.amount', 0),
                    'sale_price' => $price,
                    'unit_profit' =>  $unit_profit,
                    'net_profit' =>  $net_profit,
                    'purchase_price' =>  $purchase_price,
                    'tax' => 0,
                    'total_price' => $item['final_price'],
                    'slug' => Str::orderedUuid(),
                ]);

                // only confirmed orders can decrement stock
                if ($is_confirmed) {
                    $this->allocatePosSaleUnits($order, $productOrderProduct, $product, $item, $warehouseId, $variantId);

                    // decrement stock
                    if ($variantId) {
                        $variant = ProductVariantCombination::find($variantId);
                        if ($variant) {
                            $variant->decrement('stock', $item['qty']);
                        }
                    }
                    $product->decrement('stock', $item['qty']);
                    $this->decrementPosProductStock($product, $warehouseId, $variantId, (float) $item['qty']);

                    if ($variantId) {
                        ProductStockLog::create([
                            'product_id' => $product->id,
                            'warehouse_id' => $warehouseId,
                            'product_name' => $product->name,
                            'product_sales_id' => $order->id,
                            'variant_combination_id' => $variantId,
                            'quantity' => $item['qty'],
                            'type' => 'sales',
                            'has_variant' => 1,
                            'creator' => Auth::id(),
                            'slug' => uniqid() . time(),
                            'status' => 'active',
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                    } else {
                        ProductStockLog::create([
                            'product_id' => $product->id,
                            'warehouse_id' => $warehouseId,
                            'product_name' => $product->name,
                            'product_sales_id' => $order->id,
                            'quantity' => $item['qty'],
                            'type' => 'sales',
                            'has_variant' => 0,
                            'creator' => Auth::id(),
                            'slug' => uniqid() . time(),
                            'status' => 'active',
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                    }
                }
            }

            $this->updatePosOrderProfitSummary($order);

            if ($is_confirmed) {
                app(\App\Services\Commission\OrderCommissionService::class)->calculateForOrder($order->fresh());
            }

            $random_no = random_int(100, 999) . random_int(1000, 9999);
            $order->slug = $order->id . Str::orderedUuid() . uniqid() . $random_no;
            $order->save();

            if ($is_confirmed) {
                $this->persistPosCustomerPayments($order, $paymentLines, $advanceUsed);
                if ($customer) {
                    calc_customer_balance($customer->id);
                }

                // Record accounting transactions before commit
                $this->recordPosOrderAccounting($order, $paymentLines, $advanceUsed, $cart, $totals);

                /** auth user target update */
                $this->updateUserTarget($order);
                // db_customer_payments
                // $this->recordCustomerPayment($order);
            }

            DB::commit();
            $this->dispatchPosExternalActions(
                $order,
                $deliveryInfo,
                $request->boolean('sms_send_to_customer'),
                $warehouseId,
                $totalPaid
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'order_slug' => $order->slug,
                    'order_code' => $order->order_code,
                    'print_url' => route('pos.desktop.print', $order->slug),
                ],
                'message' => 'Order created successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            // throw $e;
            return response()->json([
                'success' => false,
                'message' => 'Order creation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function createQuotation($warehouseId, $customerId, $customerPhone, $customerName, $orderStatus, $grandTotal, $totalPaid, $customerAddress, $totals, $paymentLines, $advanceUsed, $cart, $deliveryInfo, $orderSource = 'pos', $customerPayload = [])
    {
        DB::beginTransaction();
        try {
            $order = new ProductOrderQuotation();
            $order->store_id = auth()->user()->store_id;
            $order->order_code = $this->generateQuotationCode();
            $order->product_warehouse_id = $warehouseId;
            $order->customer_id = $customerId;
            $order->customer_phone = $customerPhone;
            $order->customer_name = $customerName;
            $this->applyPosCustomerSnapshot($order, 'product_order_quotations', $customerId, is_array($customerPayload) ? $customerPayload : []);
            $order->sale_date = Carbon::now();
            $order->subtotal = data_get($totals, 'subtotal', 0);
            $order->other_charges = $this->buildPosOtherChargesPayload($totals);
            $order->other_charge_amount = ((float) data_get($totals, 'extra_charge', 0)) + ((float) data_get($totals, 'delivery_charge', 0));
            $order->discount_type = data_get($totals, 'discount.type', null);
            $order->discount_amount = data_get($totals, 'discount.value', 0);
            $order->calculated_discount_amount = data_get($totals, 'discount.amount', 0);
            $order->round_off_from_total = data_get($totals, 'round_off', 0);
            $order->decimal_round_off = data_get($totals, 'round_off', 0);
            $order->total = $grandTotal;
            $order->paid_amount = $totalPaid;
            $order->due_amount = $grandTotal - $totalPaid;
            $order->address = $customerAddress;
            $order->payments = array_merge(
                $this->paymentsArrayFromLines($paymentLines),
                [
                    'advance_used' => $advanceUsed,
                    'total_paid' => $totalPaid,
                    'total_due' => max(0, $grandTotal - $totalPaid),
                ]
            );
            $order->note = $deliveryInfo['order_note'] ?? null;
            $order->order_source = $orderSource ?: 'pos';
            $order->order_status = $orderStatus;
            $order->delivery_info = $deliveryInfo;
            $order->creator = Auth::id();
            $order->status = 'active';
            $order->created_at = Carbon::now();
            $order->save();

            foreach ($cart as $item) {
                $product = Product::findOrFail($item['product_id']);
                $variantId = $item['variant_id'] ?? null;
                $purchase_price = DB::table('product_purchase_order_products')
                ->where('product_id', $item['product_id'])
                ->orderBy('id', 'desc')
                ->value('purchase_price');
                $price = $this->posCartLineSalePrice($item, $product);
                $unit_profit = ($price - $purchase_price);
                $net_profit = ($unit_profit * $item['qty']);

                $productOrderProduct = ProductOrderQuotationProduct::create([
                    'product_warehouse_id' => $warehouseId,
                    'product_warehouse_room_id' => null,
                    'product_warehouse_room_cartoon_id' => null,
                    'product_supplier_id' => null,
                    'product_order_quotation_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variantId,
                    'unit_price_id' => null,
                    'product_name' => $product->name,
                    'qty' => $item['qty'],
                    'sale_price' => $this->posCartLineSalePrice($item, $product),
                    'product_image' => $product->image,
                    'discount_type' => data_get($item, 'discount.type', 'in_percentage'),
                    // 'discount_amount' => data_get($item, 'discount.value', 0),
                    'discount_amount' => data_get($item, 'discount.amount', 0),
                    'tax' => 0,
                    'total_price' => $item['final_price'],
                    'product_price' => $item['unit_price'] ?? ($product->discount_price ?: $product->price),
                    'slug' => Str::orderedUuid(),
                    'unit_profit' =>  $unit_profit,
                    'net_profit' =>  $net_profit,
                    'purchase_price' =>  $purchase_price,
                ]);
            }

            $random_no = random_int(100, 999) . random_int(1000, 9999);
            $order->slug = $order->id . Str::orderedUuid() . uniqid() . $random_no;
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'quotation_id' => $order->id,
                    'quotation_slug' => $order->slug,
                    'quotation_code' => $order->order_code,
                    // 'print_url' => route('pos.desktop.print-quotation', $order->slug),
                    'print_url' => url(route('order.invoice', ['slug' => $order->slug, 'type' => 'quotation'])),
                ],
                'message' => 'Quotation created successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            // throw $e;
            return response()->json([
                'success' => false,
                'message' => 'Quotation creation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function editOrder(Request $request)
    {
        $action = $request->get('action', 'update');
        $orderId = (int) $request->get('order_id');

        if ($action === 'load') {
            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID is required.',
                ], 422);
            }

            $order = ProductOrder::with(['order_products', 'customer'])
                ->where('id', $orderId)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->buildPosEditPayload($order),
            ]);
        }

        $payload = $request->validate([
            'order_id' => 'required|integer|exists:product_orders,id',
            'cart' => 'required|array|min:1',
            'totals' => 'required|array',
            'customer.id' => 'nullable|integer|exists:customers,id',
            'customer.name' => 'nullable|string|max:255',
            'payments' => 'nullable|array',
            'payments.*.amount' => 'nullable|numeric|min:0',
            'payments.*.payment_type_id' => 'required_with:payments.*.amount|nullable|integer|exists:db_paymenttypes,id',
            'use_advance' => 'boolean',
            'advance_amount' => 'nullable|numeric|min:0',
            'order_note' => 'nullable|string|max:500',
            'order_source' => 'nullable|string|max:50',
        ]);

        $order = ProductOrder::with(['order_products'])->findOrFail((int) $payload['order_id']);

        $cart = $payload['cart'];
        $totals = $payload['totals'];
        $paymentLines = $request->get('payments', []) ?? [];
        $useAdvance = (bool) ($payload['use_advance'] ?? false);
        $warehouseId = $request->get('warehouse_id');
        $deliveryInfo = $request->get('delivery_info', []) ?? [];
        $orderStatus = $request->get('order_status', $order->order_status ?? 'quotation');

        $customerAddress = request()->get('customer')['address'] ?? null;
        $customerPhone = request()->get('customer')['phone'] ?? null;
        $customerName = request()->get('customer')['name'] ?? null;

        $serverTotals = $this->calculateOrderTotalsFromCart($cart, $totals);
        $totals = $serverTotals;
        $grandTotal = $serverTotals['grand_total'];
        $cart = $serverTotals['cart'];
        $paymentTotal = 0;
        foreach ($paymentLines as $line) {
            if (!empty($line['amount'])) {
                $paymentTotal += (float) $line['amount'];
            }
        }

        $customerId = data_get($payload, 'customer.id');
        $advanceUsed = 0;
        $customer = null;

        $oldOrderStatus = $order->order_status;
        $oldConfirmed = in_array($oldOrderStatus, ['invoiced', 'delivered']);
        $isConfirmed = in_array($orderStatus, ['invoiced', 'delivered']);
        $oldAdvanceUsed = (float) data_get(
            is_array($order->payments) ? $order->payments : (json_decode($order->payments, true) ?: []),
            'advance_used',
            0
        );

        if ($customerId && $customerId > 1) {
            $customer = Customer::findOrFail($customerId);
            if ($customerAddress) {
                $customer->address = $customerAddress;
                $customer->save();
            }
            if ($useAdvance) {
                $availableAdvance = (float) ($customer->available_advance ?? 0);
                if ($oldConfirmed && (int) $order->customer_id === (int) $customer->id) {
                    $availableAdvance += $oldAdvanceUsed;
                }

                $providedAdvanceAmount = data_get($payload, 'advance_amount', 0);
                if ($providedAdvanceAmount > 0) {
                    $advanceUsed = min($providedAdvanceAmount, $availableAdvance, max(0, $grandTotal - $paymentTotal));
                } else {
                    if ($availableAdvance > 0) {
                        $advanceUsed = min($availableAdvance, max(0, $grandTotal - $paymentTotal));
                    }
                }
            }
        }

        $totalPaid = $paymentTotal + $advanceUsed;

        if ($customerId == 1 && abs($totalPaid - $grandTotal) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Walking customer orders must be fully paid. Due amounts are not allowed for walk-in customers.',
            ], 422);
        }

        $newDueAmount = max(0, $grandTotal - $totalPaid);
        if ($customer && $newDueAmount > 0) {
            if (!$customer->allow_due) {
                return response()->json([
                    'success' => false,
                    'message' => 'Due sale is disabled for this customer.',
                ], 422);
            }

            $currentDue = (float) ProductOrder::where('customer_id', $customer->id)->directCustomerReceivable()->sum('due_amount');
            if ((int) $order->customer_id === (int) $customer->id) {
                $currentDue -= (float) ($order->due_amount ?? 0);
            }
            $creditLimit = (float) ($customer->credit_limit ?? 0);
            if ($creditLimit > 0 && ($currentDue + $newDueAmount) > $creditLimit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer credit limit exceeded.',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            if ($oldConfirmed) {
                $this->rollbackPosOrderEffects($order);
            }

            $order->product_warehouse_id = $warehouseId;
            $order->customer_id = $customerId;
            $order->customer_phone = $customerPhone;
            $order->customer_name = $customerName;
            $this->applyPosCustomerSnapshot($order, 'product_orders', $customerId, data_get($payload, 'customer', []));
            $order->sale_date = Carbon::now();
            $order->subtotal = data_get($totals, 'subtotal', 0);
            $order->other_charges = $this->buildPosOtherChargesPayload($totals);
            $order->other_charge_amount = ((float) data_get($totals, 'extra_charge', 0)) + ((float) data_get($totals, 'delivery_charge', 0));
            $order->discount_type = data_get($totals, 'discount.type', null);
            $order->discount_amount = data_get($totals, 'discount.value', 0);
            $order->calculated_discount_amount = data_get($totals, 'discount.amount', 0);
            $order->round_off_from_total = data_get($totals, 'round_off', 0);
            $order->decimal_round_off = data_get($totals, 'round_off', 0);
            $order->total = $grandTotal;
            $order->paid_amount = $totalPaid;
            $order->due_amount = max(0, $grandTotal - $totalPaid);
            $order->address = $customerAddress;
            $order->payments = array_merge(
                $this->paymentsArrayFromLines($paymentLines),
                [
                    'advance_used' => $advanceUsed,
                    'total_paid' => $totalPaid,
                    'total_due' => max(0, $grandTotal - $totalPaid),
                ]
            );
            $order->note = $deliveryInfo['order_note'] ?? null;
            $order->order_source = $payload['order_source'] ?? $order->order_source ?? 'pos';
            $order->order_status = $orderStatus;
            $order->delivery_info = $deliveryInfo;
            $order->salesman_id = $request->get('salesman_id') ?: ($order->salesman_id ?: $order->creator);
            $order->affiliate_code = $request->get('affiliate_code') ?: data_get($payload, 'affiliate_code', $order->affiliate_code);
            if ($order->affiliate_code) {
                $affiliate = \App\Models\Affiliate::where('code', $order->affiliate_code)->where('status', 'active')->first();
                $order->affiliate_id = $affiliate ? $affiliate->id : $order->affiliate_id;
            }
            $order->updated_at = Carbon::now();
            $order->save();

            ProductOrderProductAllocation::where('product_order_id', $order->id)->delete();
            ProductOrderProduct::where('product_order_id', $order->id)->delete();

            foreach ($cart as $item) {
                $product = Product::findOrFail($item['product_id']);
                $variantId = $item['variant_id'] ?? null;
                $purchase_price = DB::table('product_purchase_order_products')
                ->where('product_id', $item['product_id'])
                ->orderBy('id', 'desc')
                ->value('purchase_price');
                $price = $this->posCartLineSalePrice($item, $product);
                $unit_profit = ( $price - $purchase_price );
                $net_profit = ( $unit_profit * $item['qty'] );

                $productOrderProduct = ProductOrderProduct::create([
                    'product_warehouse_id' => $warehouseId,
                    'product_warehouse_room_id' => null,
                    'product_warehouse_room_cartoon_id' => null,
                    'product_supplier_id' => null,
                    'product_order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variantId,
                    'unit_price_id' => null,
                    'product_name' => $product->name,
                    'qty' => $item['qty'],
                    'sale_price' => $this->posCartLineSalePrice($item, $product),
                    'discount_type' => data_get($item, 'discount.type', 'in_percentage'),
                    // 'discount_amount' => round($perUnitDiscount, 2),
                    // 'discount_type' => data_get($item, 'discount.type', 'in_percentage'),
                    'discount_amount' => data_get($item, 'discount.value', 0),
                    // 'discount_amount' => data_get($item, 'discount.amount', 0),
                    'tax' => 0,
                    'total_price' => $item['final_price'],
                    'product_price' => $item['unit_price'] ?? ($product->discount_price ?: $product->price),
                    'slug' => Str::orderedUuid(),
                    'unit_profit' =>  $unit_profit,
                    'net_profit' =>  $net_profit,
                    'purchase_price' =>  $purchase_price,
                ]);

                if ($isConfirmed) {
                    $this->allocatePosSaleUnits($order, $productOrderProduct, $product, $item, $warehouseId, $variantId);

                    if ($variantId) {
                        $variant = ProductVariantCombination::find($variantId);
                        if ($variant) {
                            $variant->decrement('stock', $item['qty']);
                        }
                    }
                    $product->decrement('stock', $item['qty']);

                    $this->decrementPosProductStock($product, $warehouseId, $variantId, (float) $item['qty']);

                    if ($variantId) {
                        ProductStockLog::create([
                            'product_id' => $product->id,
                            'warehouse_id' => $warehouseId,
                            'product_name' => $product->name,
                            'product_sales_id' => $order->id,
                            'variant_combination_id' => $variantId,
                            'quantity' => $item['qty'],
                            'type' => 'sales',
                            'has_variant' => 1,
                            'creator' => Auth::id(),
                            'slug' => uniqid() . time(),
                            'status' => 'active',
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                    } else {
                        ProductStockLog::create([
                            'product_id' => $product->id,
                            'warehouse_id' => $warehouseId,
                            'product_name' => $product->name,
                            'product_sales_id' => $order->id,
                            'quantity' => $item['qty'],
                            'type' => 'sales',
                            'has_variant' => 0,
                            'creator' => Auth::id(),
                            'slug' => uniqid() . time(),
                            'status' => 'active',
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                    }
                }
            }

            $this->updatePosOrderProfitSummary($order);

            if ($isConfirmed) {
                app(\App\Services\Commission\OrderCommissionService::class)->calculateForOrder($order->fresh());
            }

            if ($isConfirmed) {
                $this->persistPosCustomerPayments($order, $paymentLines, $advanceUsed);
                if ($customer) {
                    calc_customer_balance($customer->id);
                }

                $this->recordPosOrderAccounting($order, $paymentLines, $advanceUsed, $cart, $totals);
            }

            if ($oldConfirmed || $isConfirmed) {
                $this->updateUserTarget($order);
            }

            DB::commit();
            $this->dispatchPosExternalActions($order, $deliveryInfo, false, $warehouseId, $totalPaid);

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'order_slug' => $order->slug,
                    'order_code' => $order->order_code,
                    'print_url' => route('pos.desktop.print', $order->slug),
                ],
                'message' => 'Order updated successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Order update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function buildPosEditPayload(ProductOrder $order): array
    {
        $order->loadMissing(['order_products', 'customer', 'salesman', 'affiliate']);

        $productIds = $order->order_products->pluck('product_id')->filter()->unique()->values()->all();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $unitCodesByOrderProduct = ProductPurchaseOrderProductUnit::whereIn('product_order_product_id', $order->order_products->pluck('id'))
            ->where('sale_id', $order->id)
            ->pluck('code', 'product_order_product_id');

        $cart = $order->order_products->map(function ($item) use ($products, $unitCodesByOrderProduct) {
            $product = $products->get($item->product_id);
            $qty = (float) ($item->qty ?? 0);
            $salePrice = (float) ($item->sale_price ?? 0);
            $totalPrice = (float) ($item->total_price ?? 0);
            $discountType = $item->discount_type ?: 'fixed';
            $discountValue = (float) ($item->discount_amount ?? 0);
            $maxQty = (float) ($product->stock ?? 0);

            return [
                'temp_id' => 'edit-' . $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'variant_combination_key' => data_get($item, 'variant.combination_key'),
                'qty' => $qty,
                'max_qty' => $maxQty > 0 ? $maxQty + $qty : $qty,
                'title' => $item->product_name ?: ($product->name ?? 'Item'),
                'image_url' => get_file_url() . '/' . data_get($product, 'image'),
                'unit_price' => $salePrice,
                'main_price' => (float) data_get($product, 'price', $salePrice),
                'discount_price' => max(0, $salePrice - $discountValue),
                'discount_parcent' => 0,
                'discount' => [
                    'type' => $discountType,
                    'value' => $discountValue,
                    'fixed' => $discountType === 'fixed' ? $discountValue : 0,
                    'percent' => $discountType === 'percent' ? $discountValue : 0,
                ],
                'final_price' => $totalPrice,
                // 'unit_code' => $unitCodesByOrderProduct[$item->id] ?? null,
            ];
        })->values()->all();

        $payments = is_array($order->payments) ? $order->payments : (json_decode($order->payments, true) ?: []);
        $paymentMethods = collect($payments)
            ->except(['advance_used', 'total_paid', 'total_due'])
            ->map(function ($amount, $method) {
                return [
                    'method' => $method,
                    'amount' => (float) $amount,
                ];
            })
            ->values()
            ->all();

        $customer = $order->customer;
        $customerPayload = $customer ? $this->posCustomerPayload($customer) : null;
        $deliveryInfo = is_array($order->delivery_info) ? $order->delivery_info : (json_decode($order->delivery_info, true) ?: []);
        $otherCharges = is_array($order->other_charges) ? $order->other_charges : (json_decode($order->other_charges, true) ?: []);
        $extraChargeLines = $this->normalizePosExtraChargeLines(data_get($otherCharges, 'extra_charge_lines', []));

        return [
            'order_id' => $order->id,
            'order_slug' => $order->slug,
            'warehouse_id' => $order->product_warehouse_id,
            'cart' => $cart,
            'totals' => [
                'subtotal' => (float) ($order->subtotal ?? 0),
                'discount' => [
                    'type' => $order->discount_type ?: 'fixed',
                    'value' => (float) ($order->discount_amount ?? 0),
                    'amount' => (float) ($order->calculated_discount_amount ?? 0),
                ],
                'coupon' => [
                    'code' => '',
                    'percent' => 0,
                    'type' => '',
                    'value' => 0,
                    'amount' => 0,
                ],
                'extra_charge' => (float) data_get($otherCharges, 'extra_charge', 0),
                'extra_charge_lines' => $extraChargeLines,
                'delivery_charge' => (float) data_get($otherCharges, 'delivery_charge', 0),
                'round_off' => (float) ($order->round_off_from_total ?? 0),
                'grand_total' => (float) ($order->total ?? 0),
            ],
            'customer' => $customerPayload
                ? array_merge($customerPayload, [
                    'selected_contact_person_id' => $order->customer_contact_person_id ?: data_get($customerPayload, 'selected_contact_person_id'),
                ])
                : [
                    'id' => $order->customer_id,
                    'name' => $order->customer_name,
                    'phone' => $order->customer_phone,
                    'email' => null,
                    'address' => $order->address,
                    'image' => null,
                    'advance' => 0,
                ],
            'payments' => $paymentMethods,
            'use_advance' => ((float) data_get($payments, 'advance_used', 0)) > 0,
            'advance_amount' => (float) data_get($payments, 'advance_used', 0),
            'delivery_info' => $deliveryInfo,
            'salesman_id' => $order->salesman_id,
            'salesman_name' => optional($order->salesman)->name,
            'affiliate_id' => $order->affiliate_id,
            'affiliate_code' => $order->affiliate_code,
            'affiliate_name' => optional($order->affiliate)->name,
            'commission_status' => $order->commission_status,
            'commission_total' => (float) ($order->commission_total ?? 0),
            'order_status' => $order->order_status ?: 'quotation',
        ];
    }

    protected function rollbackPosOrderEffects(ProductOrder $order): void
    {
        app(\App\Services\Commission\OrderCommissionService::class)->reverseForOrder($order);

        $order->loadMissing('order_products');

        ProductPurchaseOrderProductUnit::where('sale_id', $order->id)
            ->update([
                'sale_id' => null,
                'unit_status' => 'instock',
                'product_order_product_id' => null,
                'customer_warranty_start_date' => null,
                'customer_warranty_end_date' => null,
                'updated_at' => Carbon::now(),
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

            $stockQuery = ProductStock::where('product_id', $item->product_id)
                ->where('product_warehouse_id', $order->product_warehouse_id)
                ->where('status', 'active');

            if ($item->variant_id) {
                $stockQuery->where('variant_combination_id', $item->variant_id);
            }

            $stockQuery->increment('qty', $qty);
        }

        ProductStockLog::where('product_sales_id', $order->id)
            ->where('type', 'sales')
            ->delete();

        ProductOrderProductAllocation::where('product_order_id', $order->id)->delete();
        AcTransaction::where('ref_sales_id', $order->id)->delete();
        DbCustomerPayment::where('order_id', $order->id)->delete();
        OrderPayment::where('order_id', $order->id)->delete();

        if ($order->customer_id) {
            calc_customer_balance($order->customer_id);
        }
    }

    /**
     * Return printable HTML for preview popup.
     */
    public function preview(Request $request)
    {
        $company = [
            'name' => 'BME Trading Company',
            'address' => '123 Business District, Dhaka-1000, Bangladesh',
            'phone' => '+880 1700-000000',
            'email' => 'info@bmetrading.com',
            'website' => 'www.bmetrading.com',
            'logo' => '/logo.png',
        ];

        $orderSlug = $request->get('order_slug');
        if ($orderSlug) {
            $order = ProductOrder::with(['order_products.variant', 'order_products.unitPrice', 'customer', 'warehouse'])
                ->where('slug', $orderSlug)
                ->firstOrFail();

            $qrData = (config('app.app_frontend_url') . '/order-invoice/' . $order->slug);
        } else {
            $payload = $request->validate([
                'cart' => 'required|array|min:1',
                'totals' => 'required|array',
                'customer' => 'nullable|array',
                'order_note' => 'nullable|string|max:500',
            ]);
            $warehouseId = $request->get('warehouse_id');
            $order = $this->makePreviewOrder($payload, $warehouseId);
            $qrData = 'POS PREVIEW';
        }

        $html = view('invoice.product-order', compact('order', 'company', 'qrData'))->render();

        return response()->json([
            'success' => true,
            'data' => [
                'html' => $html,
            ],
        ]);
    }

    protected function makePreviewOrder(array $payload, $warehouseId = null): ProductOrder
    {
        $order = new ProductOrder();
        $totals = $payload['totals'] ?? [];

        $order->order_code = 'PREVIEW-' . now()->format('His');
        $order->sale_date = Carbon::now();
        $order->subtotal = (float) data_get($totals, 'subtotal', 0);
        $order->other_charge_amount = (float) data_get($totals, 'extra_charge', 0) + (float) data_get($totals, 'delivery_charge', 0);
        $order->other_charges = $this->buildPosOtherChargesPayload($totals);
        $order->discount_type = data_get($totals, 'discount.type', 'percent');
        $order->discount_amount = (float) data_get($totals, 'discount.value', 0);
        $order->calculated_discount_amount = (float) data_get($totals, 'discount.amount', 0) + (float) data_get($totals, 'coupon.amount', 0);
        $order->round_off_from_total = (float) data_get($totals, 'round_off', 0);
        $order->decimal_round_off = (float) data_get($totals, 'round_off', 0);
        $order->total = (float) data_get($totals, 'grand_total', 0);
        $order->paid_amount = $order->total;
        $order->due_amount = 0;
        $order->order_status = 'preview';
        $order->note = $payload['order_note'] ?? null;
        $order->order_source = 'pos';
        $order->payments = [];

        $customerData = $payload['customer'] ?? [];
        $customer = new Customer();
        $customer->name = $customerData['name'] ?? 'Walk-in customer';
        $customer->phone = $customerData['mobile'] ?? null;
        $customer->email = $customerData['email'] ?? null;
        $customer->address = $customerData['address'] ?? null;
        $order->setRelation('customer', $customer);

        if ($warehouseId) {
            $warehouse = ProductWarehouse::find($warehouseId);
        }
        if (empty($warehouse)) {
            $warehouse = new ProductWarehouse();
            $warehouse->name = 'Selected Warehouse';
        }
        $order->setRelation('warehouse', $warehouse);

        $items = collect($payload['cart'])->map(function ($item) {
            $product = new ProductOrderProduct();
            $product->product_name = $item['title'] ?? 'Item';
            $product->sale_price = (float) ($item['unit_price'] ?? 0);
            $product->qty = (float) ($item['qty'] ?? 0);
            $product->discount_amount = (float) data_get($item, 'discount.percent', 0);
            $product->tax = 0;
            $product->total_price = (float) ($item['final_price'] ?? 0);
            return $product;
        });
        $order->setRelation('order_products', $items);

        return $order;
    }

    /**
     * Print endpoint for POS that returns HTML containing POS or A4 invoice.
     */
    public function print($slug)
    {
        $order = ProductOrder::with(['order_products.variant', 'order_products.unitPrice', 'customer', 'warehouse'])
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)
                    ->orWhere('id', (int) $slug)
                    ->orWhere('order_code', $slug);
            })
            ->firstOrFail();

        $company_info = GeneralInfo::first();
        $company = [
            'name' => $company_info->company_name,
            'address' => $company_info->address,
            'phone' => $company_info->contact,
            'email' => $company_info->email,
            'website' => $company_info->website,
            'logo' => get_file_url() . '/' . $company_info->logo,
        ];

        // $qrData = route('order.invoice', ['slug' => $order->slug, 'type' => 'invoice']);
        $website = $company['website'] ?? url('/');

        if (!preg_match("~^(?:f|ht)tps?://~i", $website)) {
            $website = 'https://' . $website;
        }

        $qrData = config('app.app_frontend_url') . '/order-invoice/' . $order->slug;

        $html = view('invoice.product-order-pos', [
            'order' => $order,
            'company' => $company,
            'qrData' => $qrData,
            'orderType' => 'invoice',
        ])->render();

        return $html;
        // return response()->json([
        //     'success' => true,
        //     'data' => [
        //         'html' => $html,
        //     ],
        // ]);
    }

    public function printQuotation($slug)
    {
        $order = ProductOrderQuotation::with(['order_products.variant', 'order_products.unitPrice', 'customer', 'warehouse'])
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug)
                    ->orWhere('id', (int) $slug)
                    ->orWhere('order_code', $slug);
            })
            ->firstOrFail();
        $company_info = GeneralInfo::first();
        $company = [
            'name' => $company_info->company_name,
            'address' => $company_info->address,
            'phone' => $company_info->contact,
            'email' => $company_info->email,
            'website' => $company_info->website,
            'logo' => get_file_url() . '/' . $company_info->logo,
        ];

        $qrData = route('order.invoice', ['slug' => $order->slug, 'type' => 'quotation']);

        $html = view('invoice.product-order-pos', [
            'order' => $order,
            'company' => $company,
            'qrData' => $qrData,
            'orderType' => 'quotation',
        ])->render();

        return $html;
        // return response()->json([
        //     'success' => true,
        //     'data' => [
        //         'html' => $html,
        //     ],
        // ]);
    }

    /**
     * Generate a new POS order code (YYMM + incremental number).
     */
    protected function generateOrderCode(): string
    {
        $year = Carbon::now()->format('y');
        $month = Carbon::now()->format('m');
        $prefix = $year . $month;

        $latestOrder = ProductOrder::query()
            ->where('order_code', 'like', $prefix . '%')
            ->orderBy('order_code', 'desc')
            ->first();

        if ($latestOrder) {
            $lastNumber = (int) substr($latestOrder->order_code, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    protected function generateQuotationCode(): string
    {
        $year = Carbon::now()->format('y');
        $month = Carbon::now()->format('m');
        $prefix = $year . $month;

        $latestOrder = ProductOrderQuotation::query()
            ->where('order_code', 'like', $prefix . '%')
            ->orderBy('order_code', 'desc')
            ->first();

        if ($latestOrder) {
            $lastNumber = (int) substr($latestOrder->order_code, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Map payment methods array from POS into keyed amounts.
     */
    protected function paymentsArrayFromLines(array $lines): array
    {
        $result = [
            'cash' => 0,
            'bkash' => 0,
            'nogod' => 0,
            'rocket' => 0,
            'bank' => 0,
        ];

        foreach ($lines as $line) {
            $method = $line['method'] ?? null;
            $amount = (float) ($line['amount'] ?? 0);
            if (!$method || $amount <= 0) {
                continue;
            }

            if (!array_key_exists($method, $result)) {
                $result[$method] = 0;
            }
            $result[$method] += $amount;
        }

        return $result;
    }

    protected function persistPosCustomerPayments(ProductOrder $order, array $paymentLines, float $advanceUsed = 0): void
    {
        $user = Auth::user();

        foreach ($paymentLines as $line) {
            $amount = (float) ($line['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $paymentType = !empty($line['payment_type_id'])
                ? DbPaymentType::find($line['payment_type_id'])
                : null;

            $methodTitle = $paymentType->payment_type ?? ($line['method'] ?? 'POS');

            $orderPayment = OrderPayment::create([
                'order_id' => $order->id,
                'payment_through' => strtoupper((string) $methodTitle),
                'amount' => $amount,
                'tran_date' => $order->sale_date ?? now(),
                'store_id' => $user->store_id ?? null,
                'status' => 'VALID',
                'currency' => 'BDT',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DbCustomerPayment::create([
                'orderpayment_id' => $orderPayment->id,
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'payment_date' => $order->sale_date ?? now(),
                'payment_type' => 'received',
                'payment' => $amount,
                'payment_mode' => $paymentType->id ?? null,
                'payment_mode_title' => $methodTitle,
                'payment_note' => "POS payment for order {$order->order_code}",
                'created_by' => $user->name ?? null,
                'created_date' => now('Asia/Dhaka')->toDateString(),
                'created_time' => now('Asia/Dhaka')->format('H:i:s'),
                'system_ip' => request()->ip(),
                'system_name' => gethostname(),
                'creator' => $user->id ?? null,
                'slug' => Str::orderedUuid() . uniqid(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($advanceUsed > 0) {
            DbCustomerPayment::create([
                'orderpayment_id' => null,
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'payment_date' => $order->sale_date ?? now(),
                'payment_type' => 'adjustment',
                'payment' => -$advanceUsed,
                'payment_mode' => null,
                'payment_mode_title' => 'Advance',
                'payment_note' => "POS advance applied for order {$order->order_code}",
                'created_by' => $user->name ?? null,
                'created_date' => now('Asia/Dhaka')->toDateString(),
                'created_time' => now('Asia/Dhaka')->format('H:i:s'),
                'system_ip' => request()->ip(),
                'system_name' => gethostname(),
                'creator' => $user->id ?? null,
                'slug' => Str::orderedUuid() . uniqid(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Ensure product has a barcode and non-zero stock for local testing.
     */
    protected function ensureProductBarcode(Product $product): void
    {
        if (!$product->barcode) {
            $product->barcode = 'P' . str_pad((string) $product->id, 11, '0', STR_PAD_LEFT);
        }

        // For local/dev, ensure some stock to allow testing
        if (app()->environment('local') && (!$product->stock || $product->stock < 1)) {
            $product->stock = 0;
        }

        if ($product->isDirty()) {
            $product->save();
        }
    }

    /**
     * Ensure variant combination has a barcode and stock for testing.
     */
    protected function ensureVariantBarcode(ProductVariantCombination $variant): void
    {
        if (!$variant->barcode) {
            $variant->barcode = 'V' . str_pad((string) $variant->id, 11, '0', STR_PAD_LEFT);
        }

        if (app()->environment('local') && ($variant->stock === null || $variant->stock < 1)) {
            $variant->stock = 15;
        }

        if ($variant->isDirty()) {
            $variant->save();
        }
    }

    /**
     * Build public URL to product image or fallback.
     */
    protected function productImageUrl(?string $path): string
    {
        return get_file_url() . '/' . $path;
    }

    /**
     * Get product stock for a specific warehouse from product_stocks table.
     * This is the final calculation track after all events from a warehouse.
     * 
     * @param Product $product Product model
     * @param int|null $warehouseId Warehouse ID (optional, null means all warehouses)
     * @return int Stock quantity
     */
    protected function getProductStockForWarehouse(Product $product, $warehouseId, $code = null): int
    {
        if ($code) {
            // $stock = ProductPurchaseOrderProductUnit::where('product_id', $product->id)
            //     ->where('code', $code)
            //     ->where('unit_status', 'instock')
            //     ->count();
            // return (int) $stock;
            $stock = ProductStock::where('product_id', $product->id)
                // ->where('code', $code)
                ->first();
            return (int) $stock->qty;
        }
        $query = DB::table('product_stocks')
            ->where('product_id', $product->id);
        // ->where('status', 'active');
        // ->where('has_variant', false);

        // Filter by warehouse if provided
        if ($warehouseId !== null && $warehouseId > 0) {
            $query->where('product_warehouse_id', $warehouseId);
        }

        $qty = $query->sum('qty');

        return (int) ($qty ?? 0);
    }
    
    protected function getProductStockItemsForWarehouse(Product $product, $warehouseId)
    {
        $query = ProductStock::query()->where('product_id', $product->id);

        // Filter by warehouse if provided
        if ($warehouseId !== null && $warehouseId > 0) {
            $query->where('product_warehouse_id', $warehouseId);
        }

        $items = $query->get();

        return $items;
    }

    /**
     * Get variant stock for a specific warehouse from product_stocks table.
     * This is the final calculation track after all events from a warehouse.
     * 
     * @param ProductVariantCombination $variant Variant combination model
     * @param int|null $warehouseId Warehouse ID (optional, null means all warehouses)
     * @return float Stock quantity
     */
    protected function getVariantStockForWarehouse(ProductVariantCombination $variant, $warehouseId, $code = null): int
    {
        if ($code) {
            $stock = ProductPurchaseOrderProductUnit::where('product_id', $variant->product_id)
                ->where('code', $code)
                ->where('unit_status', 'instock')
                ->count();
            return (int) ($stock ?? 0);
        }

        $query = DB::table('product_stocks')
            ->where('product_id', $variant->product_id)
            ->where('status', 'active')
            ->where('has_variant', true);

        // Filter by variant using variant_combination_id or combination_key or barcode
        if (!empty($variant->id)) {
            $query->where(function ($q) use ($variant) {
                $q->where('variant_combination_id', $variant->id);

                if (!empty($variant->combination_key)) {
                    $q->orWhere('variant_combination_key', $variant->combination_key);
                }

                if (!empty($variant->barcode)) {
                    $q->orWhere('variant_barcode', $variant->barcode);
                }
            });
        }

        // Filter by warehouse if provided
        if ($warehouseId !== null && $warehouseId > 0) {
            $query->where('product_warehouse_id', $warehouseId);
        }

        $qty = $query->sum('qty');

        return (int) ($qty ?? 0);
    }

    /**
     * Get payment methods from DbPaymentType model
     */
    public function getPaymentMethods()
    {
        try {
            $paymentTypes = DbPaymentType::where('status', 'active')
                ->orderBy('payment_type')
                ->get(['id', 'payment_type']);

            $methods = $paymentTypes->map(function ($type) {
                // Get account for this payment type
                $account = AcAccount::where('paymenttypes_id', $type->id)
                    ->where('status', 'active')
                    ->first();

                return [
                    'id' => strtolower(str_replace(' ', '_', $type->payment_type)),
                    'payment_type_id' => $type->id,
                    'title' => $type->payment_type,
                    'account_id' => $account ? $account->id : null,
                    'account_name' => $account ? $account->account_name : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $methods,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payment methods: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record accounting transactions for POS order
     * Based on record_sales_accounting_create helper function
     */
    protected function recordPosOrderAccounting($order, $paymentLines, $advanceUsed, $cart, $totals)
    {
        $user = Auth::user();
        $accounts = app(CustomerTransactionService::class)->ensureAccountingSetup();

        // Get event mappings
        $advanceAppliedEvent = AcEventMapping::getByEventName('customer_advance_applied');
        $salesEvent = AcEventMapping::getByEventName('sales'); // POS orders are always cash sales credit->sales_revenue debit->payment_accounts

        if (!$salesEvent) {
            Log::warning('Sales event mapping not found for POS order accounting');
            return;
        }

        $payment_code = generate_payment_code('POS');
        $totalPaymentAmount = 0;

        $coupon_amount = data_get($totals, 'coupon.amount', 0);
        $discount_amount = data_get($totals, 'discount.amount', 0);
        $round_off_amount = data_get($totals, 'round_off', 0);

        $sales_revenue_amount = data_get($totals, 'subtotal', 0);
        $extra_charge_amount = data_get($totals, 'extra_charge', 0);
        $delivery_charge_amount = data_get($totals, 'delivery_charge', 0);

        /** save asset debit */
        foreach ($paymentLines as $payment_type) {
            if ($payment_type['amount'] > 0) {
                $payment_info = DbPaymentType::find($payment_type['payment_type_id']);
                $totalPaymentAmount += $payment_type['amount'];
                if ($payment_info) {
                    $pt = strtolower($payment_info->payment_type ?? '');
                    $paymentNote = str_contains($pt, 'cash')
                        ? "POS Sale - Cash received from customer for order {$order->order_code}"
                        : (str_contains($pt, 'bank')
                            ? "POS Sale - Bank payment received from customer for order {$order->order_code}"
                            : "POS Sale - Mobile wallet payment received from customer for order {$order->order_code}");
                    AcTransaction::create([
                        'store_id' => $user->store_id ?? null,
                        'payment_code' => $payment_code,
                        'transaction_date' => Carbon::today()->format('Y-m-d'),
                        'transaction_type' => 'Asset',
                        'debit_account_id' => $payment_info->debit_account_id,
                        'credit_account_id' => null,
                        'debit_amt' => $payment_type['amount'],
                        'credit_amt' => null,
                        'note' => $paymentNote,
                        'customer_id' => $order->customer_id,
                        'ref_sales_id' => $order->id,
                        'created_by' => substr($user->name, 0, 50),
                        'creator' => $user->id,
                        'slug' => uniqid() . time(),
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }

        /** advance advance debit */
        if ($advanceUsed > 0) {
            $totalPaymentAmount += $advanceUsed;
            $advanceDebitAccountId = $advanceAppliedEvent->debit_account_id
                ?? $accounts['customer_advance']->id
                ?? null;

            if (!$advanceDebitAccountId) {
                Log::warning('Customer advance account missing for POS order accounting', [
                    'order_id' => $order->id,
                    'advance_used' => $advanceUsed,
                ]);
                throw new \Exception('Customer advance account missing for POS order accounting.');
            }

            AcTransaction::create([
                'store_id' => $user->store_id ?? null,
                'payment_code' => $payment_code,
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'transaction_type' => 'POS_SALE',
                'debit_account_id' => $advanceDebitAccountId,
                'credit_account_id' => null,
                'debit_amt' => $advanceUsed,
                'credit_amt' => null,
                'note' => "POS Sale - Applied customer advance to reduce payment for order {$order->order_code}",
                'customer_id' => $order->customer_id,
                'ref_sales_id' => $order->id,
                'created_by' => substr($user->name, 0, 50),
                'creator' => $user->id,
                'slug' => uniqid() . time(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        /** customer due debit */
        $totalDue = $order->total - $totalPaymentAmount;
        if ($totalDue > 0) {
            $dueMapping = AcEventMapping::getByEventName('customer_due_order');

            if (!$dueMapping || !$dueMapping->debit_account_id) {
                Log::warning('Customer due account mapping missing for POS order accounting', [
                    'order_id' => $order->id,
                    'due' => $totalDue,
                ]);
                throw new \Exception('Customer due account mapping missing for POS order accounting.');
            }

            AcTransaction::create([
                'store_id' => $user->store_id ?? null,
                'payment_code' => $payment_code,
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'transaction_type' => 'POS_SALE',
                'debit_account_id' => $dueMapping->debit_account_id,
                'credit_account_id' => null,
                'debit_amt' => $totalDue,
                'credit_amt' => null,
                'note' => "POS Sale - Customer due recorded for unpaid portion of order {$order->order_code}",
                'customer_id' => $order->customer_id,
                'ref_sales_id' => $order->id,
                'created_by' => substr($user->name, 0, 50),
                'creator' => $user->id,
                'slug' => uniqid() . time(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        /** promotional discount debits */
        if ($coupon_amount > 0) {
            AcTransaction::create([
                'store_id' => $user->store_id ?? null,
                'payment_code' => $payment_code,
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'transaction_type' => 'POS_SALE',
                'debit_account_id' => $accounts['sales_discount']->id,
                'credit_account_id' => null,
                'debit_amt' => $coupon_amount,
                'credit_amt' => null,
                'note' => "POS Sale - Coupon applied for promotional discount for order {$order->order_code}",
                'customer_id' => $order->customer_id,
                'ref_sales_id' => $order->id,
                'created_by' => substr($user->name, 0, 50),
                'creator' => $user->id,
                'slug' => uniqid() . time(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        /** discount debits */
        if ($discount_amount > 0) {
            AcTransaction::create([
                'store_id' => $user->store_id ?? null,
                'payment_code' => $payment_code,
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'transaction_type' => 'POS_SALE',
                'debit_account_id' => $accounts['sales_discount']->id,
                'credit_account_id' => null,
                'debit_amt' => $discount_amount,
                'credit_amt' => null,
                'note' => "POS Sale - Shop discount allowed for order {$order->order_code}",
                'customer_id' => $order->customer_id,
                'ref_sales_id' => $order->id,
                'created_by' => substr($user->name, 0, 50),
                'creator' => $user->id,
                'slug' => uniqid() . time(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        /** round off debits */
        if ($round_off_amount > 0) {
            AcTransaction::create([
                'store_id' => $user->store_id ?? null,
                'payment_code' => $payment_code,
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'transaction_type' => 'POS_SALE',
                'debit_account_id' => $accounts['round_off']->id,
                'credit_account_id' => null,
                'debit_amt' => $round_off_amount,
                'credit_amt' => null,
                'note' => "POS Sale - Round off adjustment for order {$order->order_code}",
                'customer_id' => $order->customer_id,
                'ref_sales_id' => $order->id,
                'created_by' => substr($user->name, 0, 50),
                'creator' => $user->id,
                'slug' => uniqid() . time(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        /** sales revenue credits */
        if ($sales_revenue_amount > 0) {
            AcTransaction::create([
                'store_id' => $user->store_id ?? null,
                'payment_code' => $payment_code,
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'transaction_type' => 'POS_SALE',
                'debit_account_id' => null,
                'credit_account_id' => $accounts['sales_revenue']->id,
                'debit_amt' => null,
                'credit_amt' => $sales_revenue_amount,
                'note' => "POS Sale - Sales revenue recorded for order {$order->order_code} (subtotal of items)",
                'customer_id' => $order->customer_id,
                'ref_sales_id' => $order->id,
                'created_by' => substr($user->name, 0, 50),
                'creator' => $user->id,
                'slug' => uniqid() . time(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        /** extra charge credits */
        if ($extra_charge_amount > 0) {
            AcTransaction::create([
                'store_id' => $user->store_id ?? null,
                'payment_code' => $payment_code,
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'transaction_type' => 'POS_SALE',
                'debit_account_id' => null,
                'credit_account_id' => $accounts['extra_charge_income']->id,
                'debit_amt' => null,
                'credit_amt' => $extra_charge_amount,
                'note' => "POS Sale - Extra charge collected for order {$order->order_code} (service/handling fees)",
                'customer_id' => $order->customer_id,
                'ref_sales_id' => $order->id,
                'created_by' => substr($user->name, 0, 50),
                'creator' => $user->id,
                'slug' => uniqid() . time(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        /** delivery charge credits */
        if ($delivery_charge_amount > 0) {
            AcTransaction::create([
                'store_id' => $user->store_id ?? null,
                'payment_code' => $payment_code,
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'transaction_type' => 'POS_SALE',
                'debit_account_id' => null,
                'credit_account_id' => $accounts['delivery_charge_income']->id,
                'debit_amt' => null,
                'credit_amt' => $delivery_charge_amount,
                'note' => "POS Sale - Delivery charge collected for order {$order->order_code}",
                'customer_id' => $order->customer_id,
                'ref_sales_id' => $order->id,
                'created_by' => substr($user->name, 0, 50),
                'creator' => $user->id,
                'slug' => uniqid() . time(),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        /** Inventory account **/
        $cogs = (float) ProductOrderProductAllocation::where('product_order_id', $order->id)
            ->sum(DB::raw('purchase_price * qty'));

        $cogsDebitAccountId = $salesEvent->secondary_debit_account_id;
        $inventoryCreditAccountId = $salesEvent->secondary_credit_account_id;

        if ($cogs > 0 && $cogsDebitAccountId && $inventoryCreditAccountId) {
            /** cost of goods sold debit */
            AcTransaction::create([
                'store_id' => $user->store_id ?? null,
                'payment_code' => $payment_code,
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'transaction_type' => 'COGS_POS_SALE',
                'debit_account_id' => $cogsDebitAccountId,
                'credit_account_id' => null,
                'debit_amt' => $cogs,
                'credit_amt' => null,
                'note' => "COGS - Cost of goods sold recorded for POS order {$order->order_code}",
                'customer_id' => $order->customer_id,
                'creator' => $user->id,
                'slug' => uniqid() . time(),
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'ref_sales_id' => $order->id,
            ]);

            /** stock out credit */
            AcTransaction::create([
                'store_id' => $user->store_id ?? null,
                'payment_code' => $payment_code,
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'transaction_type' => 'COGS_POS_SALE',
                'debit_account_id' => null,
                'credit_account_id' => $inventoryCreditAccountId,
                'debit_amt' => null,
                'credit_amt' => $cogs,
                'note' => "Stock Out - Inventory reduced for sold products in POS order {$order->order_code}",
                'customer_id' => $order->customer_id,
                'creator' => $user->id,
                'slug' => uniqid() . time(),
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'ref_sales_id' => $order->id,
            ]);
        }

        return '';
    }

    /**
     * Get payment account ID for a payment method
     * First tries to get from payment_type_id, then falls back to helper function
     */
    protected function getPaymentAccountIdForMethod($method, $paymentTypeId = null)
    {
        // If payment_type_id is provided, get account from that
        if ($paymentTypeId) {
            $account = AcAccount::where('paymenttypes_id', $paymentTypeId)
                ->where('status', 'active')
                ->first();

            if ($account) {
                return $account->id;
            }
        }

        // Fallback to helper function
        if (function_exists('getPaymentAccountId')) {
            return getPaymentAccountId($method);
        }

        return null;
    }

    public function customerSource()
    {
        $sources = CustomerSourceType::select('id', 'title')->where('status', 'active')->get();
        return response()->json(['success' => true, 'data' => $sources]);
    }
    public function deliveryMethods()
    {
        $methods = ProductOrderDeliveryMethod::select('id', 'title')->where('status', 'active')->get();
        return response()->json(['success' => true, 'data' => $methods]);
    }
    public function outlets()
    {
        $outlets = Outlet::select('id', 'title')->where('status', 'active')->get();
        return response()->json(['success' => true, 'data' => $outlets]);
    }
    public function courierMethods()
    {
        $methods = ProductOrderCourierMethod::select('id', 'title')->where('status', 'active')->get();
        return response()->json(['success' => true, 'data' => $methods]);
    }

    public function localDeliveryMethods()
    {
        if (!Schema::hasTable('delivery_providers')) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $methods = DeliveryProvider::query()
            ->select('id', 'name', 'provider_type')
            ->where('status', 'active')
            ->whereIn('provider_type', ['local_provider', 'internal_fleet', 'manual_provider', 'store_pickup'])
            ->where(function ($query) {
                $query->whereNull('integration_driver')
                    ->orWhereNotIn('integration_driver', ['pathao', 'steadfast']);
            })
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $methods]);
    }
    public function updateUserTarget($order)
    {
        $userId = $order->creator ?: Auth::id();
        if (!$userId) {
            return null;
        }

        $today = Carbon::today();
        $confirmedStatuses = ['invoiced', 'delivered'];

        // 1. Get monthly target (sum of all daily targets set for this month)
        $monthlyTarget = UserSalesTarget::where('user_id', $userId)
            ->whereBetween('date', [
                $today->copy()->startOfMonth(),
                $today->copy()->endOfMonth()
            ])
            ->sum('target');

        // 2. Get actual sales this month (POS orders only)
        $monthlySales = ProductOrder::where('creator', $userId)
            ->where('order_source', 'pos')
            ->whereIn('order_status', $confirmedStatuses)
            ->whereBetween('created_at', [
                $today->copy()->startOfMonth(),
                $today->copy()->endOfMonth()->endOfDay()
            ])
            ->sum('subtotal');

        // 3. Get today's sales only
        $todaySales = ProductOrder::where('creator', $userId)
            ->where('order_source', 'pos')
            ->whereIn('order_status', $confirmedStatuses)
            ->whereBetween('created_at', [
                $today->copy()->startOfDay(),
                $today->copy()->endOfDay()
            ])
            ->sum('subtotal');

        // 4. Find or create today's target record
        $todayTarget = UserSalesTarget::firstOrCreate(
            [
                'user_id' => $userId,
                'date'    => $today,
            ],
            [
                // Only set when creating new record
                'target'  => 0,           // will be updated below if needed
                'completed' => $todaySales,
                'remains' => 0,           // will be calculated below
            ]
        );

        // 5. Calculate remaining target for the month after today
        $monthRemainingTarget = max(0, $monthlyTarget - $monthlySales);

        // 6. Update today's record
        // completed = actual sales done TODAY
        // remains   = what is still needed for the rest of the month (after today)
        $todayTarget->update([
            'completed' => $todaySales,
            'remains'   => $monthRemainingTarget,
        ]);

        // Optional improvement: if you want to auto-distribute remaining target to future days
        // (only when creating new record or when monthly target changes)
        // You can add logic here later — for now keeping it simple as per your request

        return $todayTarget; // optional — for debugging or chaining
    }

    /**
     * Bulk update product order status (from order management view).
     */
    public function bulkUpdateOrderStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:product_orders,id',
            'status' => 'required|string|in:pending,invoiced,delivered,canceled',
        ]);
        $ids = $request->input('ids');
        $status = $request->input('status');
        $updated = ProductOrder::whereIn('id', $ids)->update(['order_status' => $status]);
        foreach ($ids as $id) {
            FbmOrderAttributionBridgeService::tryReconcileProductOrderById((int) $id, 'desktop_pos_bulk_status');
        }

        // send sms to customers if status changed to invoiced or delivered
        $generalInfo = GeneralInfo::where('id', 1)->first();
        if (in_array($status, ['invoiced']) && $generalInfo->sms_send_to_customer_for_ecommerce) {
            
            $orders = ProductOrder::whereIn('id', $ids)->with('customer','warehouse')->get();
            foreach ($orders as $order) {
                
                if ($order->customer && $order->customer->phone) {
                    $message = "Your order #{$order->order_code} is now confirmed. Thank you for shopping with us at {$generalInfo->company_name}!";
                    sms_send_single($order->customer->phone, $message, 'bulksmsbd');
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Status updated to \"" . $status . "\" for " . $updated . " order(s).",
            'updated' => $updated,
        ]);
    }

    /**
     * Bulk print invoices for selected product order IDs.
     * Opens in new window: /pos/desktop/print-bulk-invoices?ids=1,2,3
     */
    public function printBulkInvoices(Request $request)
    {
        $idsParam = $request->get('ids', '');
        $ids = array_filter(array_map('intval', explode(',', $idsParam)));
        if (empty($ids)) {
            abort(404, 'No order IDs provided.');
        }

        $orders = ProductOrder::with(['order_products.variant', 'order_products.unitPrice', 'customer', 'warehouse'])
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        $generalInfo = GeneralInfo::where('id', 1)->first();
        $company = [
            'name' => optional($generalInfo)->company_name ?? 'Company Name',
            'address' => optional($generalInfo)->address ?? '',
            'phone' => optional($generalInfo)->contact ?? '',
            'email' => optional($generalInfo)->email ?? '',
            'website' => str_replace(['http://', 'https://'], '', url('/')),
            'logo' => ($generalInfo && $generalInfo->logo) ? get_file_url() . '/' . $generalInfo->logo : url('/logo.png'),
        ];

        return view('invoice.product-order-pos-bulk', compact('orders', 'company'));
    }

    /**
     * Bulk print full (A4) invoices for selected product order IDs.
     * Same as printBulkInvoices but uses full invoice view (product-order).
     * URL: /pos/desktop/print-bulk-full-invoices?ids=1,2,3
     */
    public function printBulkFullInvoices(Request $request)
    {
        $idsParam = $request->get('ids', '');
        $ids = array_filter(array_map('intval', explode(',', $idsParam)));
        if (empty($ids)) {
            abort(404, 'No order IDs provided.');
        }

        $orders = ProductOrder::with(['order_products.variant', 'order_products.unitPrice', 'customer', 'warehouse'])
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        $generalInfo = GeneralInfo::where('id', 1)->first();
        $company = [
            'name' => optional($generalInfo)->company_name ?? 'Company Name',
            'address' => optional($generalInfo)->address ?? '',
            'phone' => optional($generalInfo)->contact ?? '',
            'email' => optional($generalInfo)->email ?? '',
            'website' => str_replace(['http://', 'https://'], '', url('/')),
            'logo' => ($generalInfo && $generalInfo->logo) ? get_file_url() . '/' . $generalInfo->logo : url('/logo.png'),
        ];

        return view('invoice.product-order-bulk', compact('orders', 'company'));
    }

    /**
     * Bulk email invoices for selected product order IDs.
     * GET /pos/desktop/email-bulk-invoices?ids=1,2,3
     * Loops through each order and calls ProductOrderController::emailInvoice with the customer's email.
     */
    public function emailBulkInvoices(Request $request)
    {
        $idsParam = $request->get('ids', '');
        $ids = array_filter(array_map('intval', explode(',', $idsParam)));
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No order IDs provided.',
            ], 400);
        }

        $orders = ProductOrder::with(['customer'])->whereIn('id', $ids)->orderBy('id')->get();
        $productOrderController = app(InventoryProductOrderController::class);

        $sent = 0;
        $skipped = 0;
        $errors = [];

        foreach ($orders as $order) {
            $email = trim((string) (optional($order->customer)->email ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }
            $req = Request::create('/', 'GET', ['email' => $email]);
            $response = $productOrderController->emailInvoice($req, $order->slug);
            $data = $response->getData(true);
            if ($response->getStatusCode() === 200 && !empty($data['success'])) {
                $sent++;
            } else {
                $errors[] = '#' . $order->order_code . ': ' . ($data['message'] ?? 'Failed');
            }
        }

        $message = $sent > 0
            ? 'Invoice(s) sent to ' . $sent . ' recipient(s).'
            : 'No invoices were sent.';
        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' order(s) skipped (no valid customer email).';
        }
        if (!empty($errors)) {
            $message .= ' ' . count($errors) . ' failed.';
        }

        return response()->json([
            'success' => $sent > 0 || $skipped === $orders->count(),
            'message' => $message,
            'sent' => $sent,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }

    protected function recordCustomerPayment($order)
    {
        $user = auth()->user();
        // $payments = is_array($order->payments) ? $order->payments : json_decode($order->payments, true);
        $removeKeys = ['total_paid', 'advance_used', 'total_due'];

        $payments = is_array($order->payments) 
            ? $order->payments 
            : json_decode($order->payments, true);

        $payments = array_diff_key($payments, array_flip($removeKeys));

        // Step 1: Record Order Payments
        $orderPaymentIds = [];
        foreach ($payments as $method => $amount) {
            if ($amount > 0) {
                $orderPayment = OrderPayment::create([
                    'order_id' => $order->id,
                    'payment_through' => strtoupper($method),
                    'amount' => $amount,
                    'tran_date' => $order->sale_date ?? now(),
                    'store_id' => $user->store_id ?? null,
                    'status' => 'VALID',
                    'currency' => 'BDT',
                    'created_at' => Carbon::now()
                ]);
                $orderPaymentIds[$method] = $orderPayment->id;
            }
        }

        // Step 2: Record Customer Payments (db_customer_payments)
        foreach ($payments as $method => $amount) {
                if ($amount > 0) {
                DbCustomerPayment::create([
                    'order_id' => $order->id,
                    'orderpayment_id' => $orderPaymentIds[$method] ?? null,
                    'customer_id' => $order->customer_id,
                    'payment_date' => $order->sale_date ?? now(),
                    'payment_type' => 'received',
                    'payment' => $amount,
                    'payment_note' => "Payment via {$method} for order {$order->order_code}",
                    'creator' => $user->id,
                    'slug' => Str::orderedUuid() . uniqid(),
                    'status' => 'active',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }
        }

        return ['success' => true, 'message' => 'Sales accounting recorded successfully'];
    }

    protected function normalizePosExtraChargeLines($lines): array
    {
        if (!is_array($lines)) {
            return [];
        }

        $normalized = [];
        foreach ($lines as $line) {
            if (!is_array($line)) {
                continue;
            }

            $title = trim((string) ($line['title'] ?? ''));
            $amount = round(max(0, (float) ($line['amount'] ?? 0)), 2);

            if ($title === '' && $amount <= 0) {
                continue;
            }

            $normalized[] = [
                'charge_type_id' => !empty($line['charge_type_id']) ? (int) $line['charge_type_id'] : null,
                'title' => $title !== '' ? substr($title, 0, 255) : 'Extra Charge',
                'amount' => $amount,
            ];
        }

        return $normalized;
    }

    protected function posExtraChargeLinesFromTotals(array $totals): array
    {
        $lines = data_get($totals, 'extra_charge_lines', data_get($totals, 'extra_charges', []));

        return $this->normalizePosExtraChargeLines($lines);
    }

    protected function buildPosOtherChargesPayload(array $totals): array
    {
        return [
            'extra_charge' => (float) data_get($totals, 'extra_charge', 0),
            'extra_charge_lines' => $this->posExtraChargeLinesFromTotals($totals),
            'delivery_charge' => (float) data_get($totals, 'delivery_charge', 0),
        ];
    }

    private function calculateOrderTotalsFromCart(array $cart, array $requestTotals): array
    {
        $subtotal = 0;

        foreach ($cart as &$item) {
            $qty = (float) data_get($item, 'qty', 1);
            $unitPrice = (float) data_get($item, 'unit_price', 0);

            $discountType = data_get($item, 'discount.type', 'percent');

            $discountPerUnit = 0;

            if ($discountType === 'percent') {
                $percent = (float) data_get($item, 'discount.percent', data_get($item, 'discount.value', 0));
                $discountPerUnit = $unitPrice * ($percent / 100);
            }

            if ($discountType === 'fixed') {
                $discountPerUnit = (float) data_get($item, 'discount.fixed', data_get($item, 'discount.value', 0));
            }

            $discountPerUnit = max(0, min($discountPerUnit, $unitPrice));

            $roundedDiscountPerUnit = round($discountPerUnit);
            $finalUnitPrice = max(0, round(max(0, $unitPrice - $roundedDiscountPerUnit) / 5) * 5);
            $finalDiscountPerUnit = max(0, $unitPrice - $finalUnitPrice);
            $lineTotal = $finalUnitPrice * $qty;

            $item['discount_price'] = $finalUnitPrice;
            $item['final_price'] = $lineTotal;
            $item['discount']['fixed'] = $finalDiscountPerUnit;
            $item['discount']['amount'] = $finalDiscountPerUnit * $qty;

            $subtotal += $lineTotal;
        }

        $orderDiscountType = data_get($requestTotals, 'discount.type');
        $orderDiscountValue = (float) data_get($requestTotals, 'discount.value', 0);

        $orderDiscountAmount = 0;

        if ($orderDiscountType === 'percent') {
            $orderDiscountAmount = $subtotal * ($orderDiscountValue / 100);
        }

        if ($orderDiscountType === 'fixed') {
            $orderDiscountAmount = $orderDiscountValue;
        }

        $orderDiscountAmount = round(max(0, min($orderDiscountAmount, $subtotal)));

        $afterDiscount = $subtotal - $orderDiscountAmount;

        $couponAmount = (float) data_get($requestTotals, 'coupon.amount', 0);
        $couponAmount = round(max(0, min($couponAmount, $afterDiscount)));

        $extraChargeLines = $this->posExtraChargeLinesFromTotals($requestTotals);
        $extraCharge = count($extraChargeLines) > 0
            ? array_sum(array_column($extraChargeLines, 'amount'))
            : (float) data_get($requestTotals, 'extra_charge', 0);
        $deliveryCharge = (float) data_get($requestTotals, 'delivery_charge', 0);
        $roundOff = (float) data_get($requestTotals, 'round_off', 0);

        $grandTotal = max(0, round(
            $afterDiscount -
                $couponAmount +
                $extraCharge +
                $deliveryCharge -
                $roundOff
        ));

        return [
            'subtotal' => $subtotal,
            'discount' => [
                'type' => $orderDiscountType,
                'value' => $orderDiscountValue,
                'amount' => $orderDiscountAmount,
            ],
            'coupon' => [
                'code' => data_get($requestTotals, 'coupon.code'),
                'type' => data_get($requestTotals, 'coupon.type'),
                'value' => data_get($requestTotals, 'coupon.value', 0),
                'percent' => data_get($requestTotals, 'coupon.percent', 0),
                'amount' => $couponAmount,
            ],
            'extra_charge' => $extraCharge,
            'extra_charge_lines' => $extraChargeLines,
            'delivery_charge' => $deliveryCharge,
            'round_off' => $roundOff,
            'grand_total' => $grandTotal,
            'cart' => $cart,
        ];
    }
}
