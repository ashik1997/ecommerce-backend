<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPackageToProductsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('products') || Schema::hasColumn('products', 'is_package')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_package')->default(false)->after('status');
        });
    }

    public function down()
    {
        // Intentionally non-destructive: preserve package-product classification.
    }
}
