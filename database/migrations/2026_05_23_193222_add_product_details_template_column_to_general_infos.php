<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductDetailsTemplateColumnToGeneralInfos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_infos', function (Blueprint $table) {
            $table->string('product_details_template', 50)->nullable()->default('v1')->comment('v1, v2,..')->after('product_card_template');
            $table->string('featured_category_product', 50)->nullable()->default('v2')->comment('v1, v2,..')->after('product_details_template');
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
            $table->dropColumn('product_details_template');
            $table->dropColumn('featured_category_product');
        });
    }
}