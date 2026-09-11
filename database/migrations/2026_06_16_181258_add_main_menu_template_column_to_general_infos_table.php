<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMainMenuTemplateColumnToGeneralInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_infos', function (Blueprint $table) {
            $table->string('main_menu_template', 50)->nullable()->default('v2')->comment('v1, v2,..')->after('product_details_template');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('general_infos', function (Blueprint $table) {
            $table->dropColumn('main_menu_template');
        });
    }
}