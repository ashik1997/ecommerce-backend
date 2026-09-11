<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIsStockRelatedToVariantGroups extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('product_stock_variant_groups')) {
            return;
        }

        if (!Schema::hasColumn('product_stock_variant_groups', 'is_stock_related')) {
            Schema::table('product_stock_variant_groups', function (Blueprint $table) {
                $table->tinyInteger('is_stock_related')->default(1)->after('is_fixed')
                    ->comment('1=>Stock-related (creates combinations); 0=>Filter-related (for frontend only)');
            });
        }

        DB::table('product_stock_variant_groups')->whereIn('slug', [
            'color', 'size', 'material', 'weight', 'storage', 'ram',
            'screen_size', 'processor', 'connectivity', 'warranty'
        ])->update(['is_stock_related' => 1]);

        DB::table('product_stock_variant_groups')->whereIn('slug', [
            'pattern', 'fit', 'sleeve_length', 'neck_type', 'type',
            'origin', 'grade', 'freshness', 'pack_size'
        ])->update(['is_stock_related' => 0]);
    }

    public function down()
    {
        // Intentionally non-destructive: preserve variant group stock behavior.
    }
}
