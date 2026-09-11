<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVariantAndUnitPriceToProductOrderProductsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('product_order_products')) {
            return;
        }

        Schema::table('product_order_products', function (Blueprint $table) {
            if (!Schema::hasColumn('product_order_products', 'variant_id')) {
                $table->unsignedBigInteger('variant_id')->nullable()->after('product_id')
                    ->comment('Product variant combination ID if product has variants');
            }
            if (!Schema::hasColumn('product_order_products', 'unit_price_id')) {
                $table->unsignedBigInteger('unit_price_id')->nullable()->after('variant_id')
                    ->comment('Product unit pricing ID if product has unit-based pricing');
            }
        });
    }

    public function down()
    {
        // Intentionally non-destructive: preserve POS order line variant and unit-price references.
    }
}
