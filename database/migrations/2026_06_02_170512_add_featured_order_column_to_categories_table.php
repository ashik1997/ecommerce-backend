<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFeaturedOrderColumnToCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('featured_order')
                ->nullable()
                ->default(0)
                ->after('featured')
                ->comment('Featured category display order');
        });

        Schema::table('subcategories', function (Blueprint $table) {
            $table->unsignedInteger('featured_order')
                ->nullable()
                ->default(0)
                ->after('featured')
                ->comment('Featured subcategory display order');
        });

        Schema::table('child_categories', function (Blueprint $table) {
            $table->tinyInteger('featured')
                ->default(0)
                ->after('name')
                ->comment("0=>Not Featured; 1=>Featured");
            $table->unsignedInteger('featured_order')
                ->nullable()
                ->default(0)
                ->after('featured')
                ->comment('Featured child category display order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('featured_order');
        });

        Schema::table('subcategories', function (Blueprint $table) {
            $table->dropColumn('featured_order');
        });

        Schema::table('child_categories', function (Blueprint $table) {
            $table->dropColumn('featured_order');
        });
    }
}
