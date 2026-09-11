<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_order_product_allocations')) {
            Schema::create('product_order_product_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_order_id')->index('popa_order_idx');
                $table->unsignedBigInteger('product_order_product_id')->index('popa_order_product_idx');
                $table->unsignedBigInteger('product_id')->index('popa_product_idx');
                $table->unsignedBigInteger('variant_id')->nullable()->index('popa_variant_idx');
                $table->unsignedBigInteger('product_purchase_order_id')->nullable()->index('popa_purchase_idx');
                $table->unsignedBigInteger('product_purchase_order_product_id')->nullable()->index('popa_purchase_product_idx');
                $table->unsignedBigInteger('product_purchase_order_product_unit_id')->nullable()->index('popa_purchase_unit_idx');
                $table->unsignedBigInteger('product_warehouse_id')->nullable()->index('popa_warehouse_idx');
                $table->decimal('qty', 16, 4)->default(0);
                $table->decimal('purchase_price', 16, 4)->default(0);
                $table->decimal('sale_price', 16, 4)->default(0);
                $table->decimal('unit_profit', 16, 4)->default(0);
                $table->decimal('net_profit', 16, 4)->default(0);
                $table->string('cost_method', 30)->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('product_order_id')->references('id')->on('product_orders')->onDelete('cascade');
                $table->foreign('product_order_product_id')->references('id')->on('product_order_products')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_order_product_allocations');
    }
};
