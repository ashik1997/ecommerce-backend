<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductPurchaseReturnProductsTable extends Migration
{
    public function up()
    {
        Schema::create('product_purchase_return_products', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_website_id')->nullable();

            $table->unsignedBigInteger('product_warehouse_id')->nullable();
            $table->unsignedBigInteger('product_warehouse_room_id')->nullable();
            $table->unsignedBigInteger('product_warehouse_room_cartoon_id')->nullable();
            $table->unsignedBigInteger('product_supplier_id')->nullable();
            $table->unsignedBigInteger('product_purchase_return_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->json('stock_codes')->nullable();
            $table->unsignedBigInteger('variant_combination_id')->nullable();
            $table->string('product_name')->nullable();
            $table->unsignedMediumInteger('qty')->nullable();
            $table->decimal('previous_stock', 12, 3)->nullable();
            $table->decimal('product_price', 10, 2)->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount_amount', 6, 2)->nullable();
            $table->decimal('tax', 6, 2)->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();

            $table->unsignedBigInteger('creator')->nullable();
            $table->string('slug')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_purchase_return_products');
    }
}
