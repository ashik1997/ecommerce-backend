<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsCourieredToProductOrdersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('product_orders') || Schema::hasColumn('product_orders', 'is_couriered')) {
            return;
        }

        Schema::table('product_orders', function (Blueprint $table) {
            $table->tinyInteger('is_couriered')->default(0)->after('order_status')->comment('0 = Not couriered, 1 = Couriered');
        });
    }

    public function down()
    {
        // Intentionally non-destructive: preserve order courier state.
    }
}
