<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfitColumnsToProductOrderQuotationProductsTable extends Migration
{
    public function up()
    {
        Schema::table('product_order_quotation_products', function (Blueprint $table) {
            $table->decimal('unit_profit', 10, 2)->default(0)->after('purchase_price');
            $table->decimal('net_profit', 10, 2)->default(0)->after('unit_profit');
        });
    }

    public function down()
    {
        Schema::table('product_order_quotation_products', function (Blueprint $table) {
            $table->dropColumn(['unit_profit', 'net_profit']);
        });
    }
}
