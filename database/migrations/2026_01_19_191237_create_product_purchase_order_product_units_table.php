<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductPurchaseOrderProductUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_purchase_order_product_units', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_website_id')->nullable();

            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_purchase_order_id')->nullable();
            $table->unsignedBigInteger('product_purchase_order_product_id')->nullable();
            $table->unsignedBigInteger('variant_combination_id')->nullable();
            $table->string('code', 10)->nullable();
            $table->string('serial_no', 100)->nullable()->index();
            $table->string('imei_1', 30)->nullable()->index();
            $table->string('imei_2', 30)->nullable()->index();
            $table->date('supplier_warranty_start_date')->nullable();
            $table->date('supplier_warranty_end_date')->nullable()->index('ppopu_supplier_warranty_end_idx');
            $table->date('customer_warranty_start_date')->nullable();
            $table->date('customer_warranty_end_date')->nullable()->index('ppopu_customer_warranty_end_idx');
            $table->text('warranty_note')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->unsignedFloat('price')->nullable();

            $table->enum('unit_status', ['pending', 'instock', 'sold', 'returned', 'lost', 'damaged'])
                ->default('instock')
                ->nullable()
                ->comment('pending=>Waiting for purchase receive; instock=>In stock; sold=>Sold; returned=>Returned; lost=>Lost; damaged=>Damaged');

            $table->unsignedBigInteger('creator')->nullable();
            $table->string('slug',)->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=>Active; 0=>Inactive');

            $table->timestamps();

            $table->foreign('product_purchase_order_id')->references('id')->on('product_purchase_orders')->onDelete('cascade');
            $table->foreign('product_purchase_order_product_id')->references('id')->on('product_purchase_order_products')->onDelete('cascade');
            $table->foreign('variant_combination_id')->references('id')->on('product_variant_combinations')->onDelete('cascade');

            $table->index('product_purchase_order_id');
            $table->index('product_purchase_order_product_id');
            $table->index('variant_combination_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_purchase_order_product_units');
    }
}
