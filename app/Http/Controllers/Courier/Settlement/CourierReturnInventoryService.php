<?php

namespace App\Http\Controllers\Courier\Settlement;

use App\Http\Controllers\Inventory\Models\ProductStock;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductPurchaseOrderProductUnit;
use App\Models\ProductStockLog;
use App\Models\ProductVariantCombination;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CourierReturnInventoryService
{
    public function restock(ProductOrder $order, ?float $returnedQty = null): float
    {
        $order->loadMissing('order_products');
        $remaining = $returnedQty ?: (float) $order->order_products->sum('qty');
        $restocked = 0;

        foreach ($order->order_products as $item) {
            if ($remaining <= 0) {
                break;
            }

            $qty = min((float) ($item->qty ?? 0), $remaining);
            if ($qty <= 0) {
                continue;
            }

            Product::whereKey($item->product_id)->increment('stock', $qty);
            if ($item->variant_id) {
                ProductVariantCombination::whereKey($item->variant_id)->increment('stock', $qty);
            }

            $stock = ProductStock::where('product_id', $item->product_id)
                ->where('status', 'active')
                ->when($item->product_warehouse_id, fn($q) => $q->where('product_warehouse_id', $item->product_warehouse_id))
                ->when($item->product_warehouse_room_id, fn($q) => $q->where('product_warehouse_room_id', $item->product_warehouse_room_id))
                ->when($item->product_warehouse_room_cartoon_id, fn($q) => $q->where('product_warehouse_room_cartoon_id', $item->product_warehouse_room_cartoon_id))
                ->when($item->variant_id, fn($q) => $q->where('variant_combination_id', $item->variant_id))
                ->orderBy('id')
                ->first();

            if ($stock) {
                ProductStock::whereKey($stock->id)->increment('qty', $qty);
            }

            $unitIds = ProductPurchaseOrderProductUnit::where('sale_id', $order->id)
                ->where('product_order_product_id', $item->id)
                ->where('unit_status', 'sold')
                ->limit((int) $qty)
                ->pluck('id');

            if ($unitIds->isNotEmpty()) {
                ProductPurchaseOrderProductUnit::whereIn('id', $unitIds)->update([
                    'sale_id' => null,
                    'product_order_product_id' => null,
                    'unit_status' => 'instock',
                    'updated_at' => Carbon::now(),
                ]);
            }

            ProductStockLog::create([
                'product_id' => $item->product_id,
                'warehouse_id' => $item->product_warehouse_id,
                'product_name' => $item->product_name,
                'product_sales_id' => $order->id,
                'variant_combination_id' => $item->variant_id ?: null,
                'quantity' => $qty,
                'type' => 'return',
                'description' => 'Courier settlement return restock',
                'has_variant' => $item->variant_id ? 1 : 0,
                'creator' => Auth::id(),
                'slug' => uniqid() . time(),
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $remaining -= $qty;
            $restocked += $qty;
        }

        return $restocked;
    }
}
