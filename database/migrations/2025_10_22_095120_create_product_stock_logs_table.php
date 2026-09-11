<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductStockLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_stock_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_website_id')->nullable();

            $table->bigInteger('warehouse_id')->unsigned()->nullable();
            $table->bigInteger('product_id')->unsigned()->nullable();
            $table->unsignedBigInteger('variant_combination_id')->nullable();
            $table->boolean('has_variant')->default(false)->comment('Flag to indicate if this log is for a product variant');
            $table->string('variant_combination_key')->nullable()->comment('Variant combination key');
            $table->string('variant_sku', 100)->nullable()->comment('Variant SKU');
            $table->json('variant_data')->nullable()->comment('JSON data containing variant attributes');
            $table->string('product_name')->nullable();

            $table->bigInteger('product_sales_id')->unsigned()->nullable();
            $table->bigInteger('product_purchase_id')->unsigned()->nullable();
            $table->bigInteger('product_return_id')->unsigned()->nullable();

            $table->integer('quantity')->nullable();
            $table->enum(
                'type',
                [
                    'sales',
                    'purchase',
                    'purchase_return',
                    'return',
                    'initial',
                    'transfer',
                    'waste',
                    'manual add'
                ]
            )
                ->nullable();
            $table->text('description')->nullable();

            $table->bigInteger('creator')->unsigned()->nullable();
            $table->string('slug', 50)->nullable();
            $table->tinyInteger('status')->unsigned()->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_stock_logs');
    }
}
