<?php

namespace App\Services\ServiceManagement;

use App\Models\Product;
use App\Models\ProductStockLog;
use App\Models\ServiceManagement\ServiceInstance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ServiceInventoryService
{
    public function assertStockAvailable(Collection $products): void
    {
        foreach ($products->groupBy('product_id') as $productId => $rows) {
            $requiredQty = (float) $rows->sum('quantity_used');
            $availableQty = $this->availableStock((int) $productId);

            if ($availableQty < $requiredQty) {
                $product = Product::find($productId);
                $productName = $product->name ?? 'Selected product';

                throw new RuntimeException($productName . ' stock is insufficient. Available: ' . $availableQty . ', required: ' . $requiredQty . '.');
            }
        }
    }

    public function applyForInstance(ServiceInstance $instance): void
    {
        if (!in_array($instance->status, ['confirmed', 'billed', 'paid'], true)) {
            return;
        }

        $instance->loadMissing(['service', 'products.product']);

        if ($instance->inventory_applied_at) {
            return;
        }

        $this->assertStockAvailable($instance->products);

        ProductStockLog::where('service_instance_id', $instance->id)->delete();

        foreach ($instance->products as $instanceProduct) {
            $quantity = (int) $instanceProduct->quantity_used;

            if ($quantity <= 0) {
                continue;
            }

            ProductStockLog::create([
                'product_id' => $instanceProduct->product_id,
                'product_name' => $instanceProduct->product->name ?? null,
                'service_instance_id' => $instance->id,
                'service_instance_product_id' => $instanceProduct->id,
                'quantity' => $quantity,
                'type' => $instance->service?->type === 'rental' ? 'rental_checkout' : 'service_usage',
                'description' => 'Service instance stock usage: ' . $instance->instance_no,
                'creator' => auth()->id(),
                'slug' => 'srms-' . $instance->id . '-' . Str::random(12),
                'status' => 1,
            ]);

            $this->decrementProductStocks($instanceProduct->product_id, $quantity);
            $this->syncProductAvailability($instanceProduct->product_id);
        }

        $instance->forceFill(['inventory_applied_at' => now()])->save();
    }

    public function returnRentalForInstance(ServiceInstance $instance): void
    {
        $instance->loadMissing(['service', 'products.product']);

        if ($instance->service?->type !== 'rental' || !$instance->inventory_applied_at || $instance->rental_returned_at) {
            return;
        }

        $this->returnInstanceProducts($instance, 'rental_return', 'Rental return for service instance: ');

        $instance->forceFill(['rental_returned_at' => now()])->save();
    }

    public function returnStockForCancelledInstance(ServiceInstance $instance): void
    {
        $instance->loadMissing(['service', 'products.product']);

        if (!$instance->inventory_applied_at) {
            return;
        }

        $type = $instance->service?->type === 'rental' ? 'rental_return' : 'return';

        if ($type === 'rental_return' && $instance->rental_returned_at) {
            return;
        }

        $this->returnInstanceProducts($instance, $type, 'Cancelled service stock return: ');

        if ($type === 'rental_return') {
            $instance->forceFill(['rental_returned_at' => now()])->save();
        }
    }

    private function returnInstanceProducts(ServiceInstance $instance, string $type, string $descriptionPrefix): void
    {
        foreach ($instance->products as $instanceProduct) {
            $quantity = (int) $instanceProduct->quantity_used;

            if ($quantity <= 0) {
                continue;
            }

            ProductStockLog::create([
                'product_id' => $instanceProduct->product_id,
                'product_name' => $instanceProduct->product->name ?? null,
                'service_instance_id' => $instance->id,
                'service_instance_product_id' => $instanceProduct->id,
                'quantity' => $quantity,
                'type' => $type,
                'description' => $descriptionPrefix . $instance->instance_no,
                'creator' => auth()->id(),
                'slug' => 'srms-in-' . $instance->id . '-' . Str::random(12),
                'status' => 1,
            ]);

            $this->incrementProductStocks($instanceProduct->product_id, $quantity);
            $this->syncProductAvailability($instanceProduct->product_id);
        }
    }

    private function availableStock(int $productId): float
    {
        $stockRowsTotal = (float) DB::table('product_stocks')
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->sum('qty');

        if ($stockRowsTotal > 0) {
            return $stockRowsTotal;
        }

        return (float) (Product::where('id', $productId)->value('stock') ?? 0);
    }

    private function decrementProductStocks(int $productId, float $quantity): void
    {
        $remaining = $quantity;
        $stockRows = DB::table('product_stocks')
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->where('qty', '>', 0)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'qty']);

        foreach ($stockRows as $stockRow) {
            if ($remaining <= 0) {
                break;
            }

            $available = (float) $stockRow->qty;
            $deduct = min($available, $remaining);

            DB::table('product_stocks')
                ->where('id', $stockRow->id)
                ->update([
                    'qty' => max(0, $available - $deduct),
                    'date' => now()->format('Y-m-d'),
                    'updated_at' => now(),
                ]);

            $remaining -= $deduct;
        }

        if ($remaining > 0) {
            Product::where('id', $productId)->decrement('stock', $remaining);
        }
    }

    private function incrementProductStocks(int $productId, float $quantity): void
    {
        $stockRow = DB::table('product_stocks')
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first(['id', 'qty']);

        if ($stockRow) {
            DB::table('product_stocks')
                ->where('id', $stockRow->id)
                ->update([
                    'qty' => (float) $stockRow->qty + $quantity,
                    'date' => now()->format('Y-m-d'),
                    'updated_at' => now(),
                ]);

            return;
        }

        Product::where('id', $productId)->update([
            'stock' => DB::raw('COALESCE(stock, 0) + ' . (float) $quantity),
            'updated_at' => now(),
        ]);
    }

    private function syncProductAvailability(int $productId): void
    {
        $stockQuery = DB::table('product_stocks')
            ->where('product_id', $productId)
            ->where('status', 'active');

        $hasStockRows = (clone $stockQuery)->exists();
        $totalStock = (float) (clone $stockQuery)->sum('qty');

        $product = Product::find($productId);

        if (!$product) {
            return;
        }

        if ($hasStockRows) {
            $product->stock = max(0, $totalStock);
        }

        $product->availability_status = (float) $product->stock > 0 ? 'in_stock' : 'out_stock';
        $product->save();
    }
}
