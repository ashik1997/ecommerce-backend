<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiFieldsToCustomersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'full_name')) {
                $table->string('full_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('customers', 'phone_original')) {
                $table->string('phone_original', 60)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('customers', 'gender')) {
                $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('email');
            }
            if (!Schema::hasColumn('customers', 'thana')) {
                $table->string('thana')->nullable()->after('address');
            }
            if (!Schema::hasColumn('customers', 'post_code')) {
                $table->string('post_code', 20)->nullable()->after('thana');
            }
            if (!Schema::hasColumn('customers', 'city')) {
                $table->string('city')->nullable()->after('post_code');
            }
            if (!Schema::hasColumn('customers', 'country')) {
                $table->string('country')->nullable()->after('city');
            }
            if (!Schema::hasColumn('customers', 'order_id')) {
                $table->string('order_id')->nullable()->after('country');
            }
        });
    }

    public function down()
    {
        // Intentionally non-destructive: preserve customer API compatibility data.
    }
}
