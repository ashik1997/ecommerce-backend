<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductFieldsToCustomerContactHistoriesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('customer_contact_histories')) {
            return;
        }

        Schema::table('customer_contact_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_contact_histories', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('employee_id');
            }
            if (!Schema::hasColumn('customer_contact_histories', 'product_name')) {
                $table->string('product_name')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('customer_contact_histories', 'subject')) {
                $table->text('subject')->nullable()->after('product_name');
            }
        });
    }

    public function down()
    {
        // Intentionally non-destructive: preserve contact history audit data.
    }
}
