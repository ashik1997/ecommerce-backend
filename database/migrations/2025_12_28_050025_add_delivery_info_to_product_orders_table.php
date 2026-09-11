<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryInfoToProductOrdersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('product_orders') || Schema::hasColumn('product_orders', 'delivery_info')) {
            return;
        }

        Schema::table('product_orders', function (Blueprint $table) {
            $table->longText('delivery_info')->nullable()->after('request_data')->comment('JSON field for delivery information');
        });
    }

    public function down()
    {
        // Intentionally non-destructive: preserve order delivery information.
    }
}
