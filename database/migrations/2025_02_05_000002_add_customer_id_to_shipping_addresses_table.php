<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerIdToShippingAddressesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('shipping_addresses')
            || Schema::hasColumn('shipping_addresses', 'customer_id')) {
            return;
        }

        Schema::table('shipping_addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('order_id');
        });
    }

    public function down()
    {
        // Intentionally non-destructive: preserve customer address relations.
    }
}
