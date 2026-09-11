<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoiceFieldsToOrdersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'invoice_no')) {
                $table->string('invoice_no')->nullable()->after('order_no');
            }
            if (!Schema::hasColumn('orders', 'invoice_date')) {
                $table->timestamp('invoice_date')->nullable()->after('order_date');
            }
            if (!Schema::hasColumn('orders', 'invoice_generated')) {
                $table->boolean('invoice_generated')->default(0)->after('complete_order');
            }
        });
    }

    public function down()
    {
        // Intentionally non-destructive: preserve generated invoice metadata.
    }
}
