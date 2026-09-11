<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductOrderProductsTable extends Migration
{
    public function up()
    {
        Schema::create('product_order_products', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_website_id')->nullable();

            $table->unsignedBigInteger('product_warehouse_id')->nullable();
            $table->unsignedBigInteger('product_warehouse_room_id')->nullable();
            $table->unsignedBigInteger('product_warehouse_room_cartoon_id')->nullable();
            $table->unsignedBigInteger('product_supplier_id')->nullable();
            $table->unsignedBigInteger('product_order_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable()->comment('Product variant combination ID if product has variants');
            $table->unsignedBigInteger('unit_price_id')->nullable()->comment('Product unit pricing ID if product has unit-based pricing');
            $table->string('product_name')->nullable();
            $table->unsignedMediumInteger('qty')->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount_amount', 6, 2)->nullable();
            $table->unsignedFloat('discount_price')->nullable();
            $table->decimal('tax', 6, 2)->nullable();
            $table->decimal('product_price', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->string('product_image', 200)->nullable();
            $table->string('price_unit', 20)->nullable();

            $table->unsignedBigInteger('creator')->nullable();
            $table->string('slug')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_order_products');
    }
}
