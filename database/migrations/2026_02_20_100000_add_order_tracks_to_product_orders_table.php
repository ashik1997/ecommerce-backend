<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderTracksToProductOrdersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('product_orders') || Schema::hasColumn('product_orders', 'order_tracks')) {
            return;
        }

        Schema::table('product_orders', function (Blueprint $table) {
            $table->json('order_tracks')->nullable()->after('delivery_info');
        });
    }

    public function down()
    {
        // Intentionally non-destructive: preserve order tracking history.
    }
}
