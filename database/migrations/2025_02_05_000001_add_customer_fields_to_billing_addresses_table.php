<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerFieldsToBillingAddressesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('billing_addresses')) {
            return;
        }

        Schema::table('billing_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('billing_addresses', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('order_id');
            }
            if (!Schema::hasColumn('billing_addresses', 'full_name')) {
                $table->string('full_name')->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('billing_addresses', 'phone')) {
                $table->string('phone')->nullable()->after('full_name');
            }
        });
    }

    public function down()
    {
        // Intentionally non-destructive: these columns may belong to the baseline schema.
    }
}
