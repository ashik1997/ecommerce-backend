<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsShippingChargeByAreaToGeneralInfosTable extends Migration
{
    public function up()
    {
        Schema::table('general_infos', function (Blueprint $table) {
            $table->tinyInteger('is_shipping_charge_by_area')
                  ->default(0)
                  ->after('is_global_free_shipping');
        });
    }

    public function down()
    {
        Schema::table('general_infos', function (Blueprint $table) {
            $table->dropColumn('is_shipping_charge_by_area');
        });
    }
}
