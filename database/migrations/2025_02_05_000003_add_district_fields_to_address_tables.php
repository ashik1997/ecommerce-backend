<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDistrictFieldsToAddressTables extends Migration
{
    public function up()
    {
        if (Schema::hasTable('billing_addresses')) {
            Schema::table('billing_addresses', function (Blueprint $table) {
                if (!Schema::hasColumn('billing_addresses', 'division_id')) {
                    $table->unsignedBigInteger('division_id')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('billing_addresses', 'district_id')) {
                    $table->unsignedBigInteger('district_id')->nullable()->after('division_id');
                }
            });
        }

        if (Schema::hasTable('shipping_addresses')) {
            Schema::table('shipping_addresses', function (Blueprint $table) {
                if (!Schema::hasColumn('shipping_addresses', 'division_id')) {
                    $table->unsignedBigInteger('division_id')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('shipping_addresses', 'district_id')) {
                    $table->unsignedBigInteger('district_id')->nullable()->after('division_id');
                }
            });
        }
    }

    public function down()
    {
        // Intentionally non-destructive: preserve existing address metadata.
    }
}
