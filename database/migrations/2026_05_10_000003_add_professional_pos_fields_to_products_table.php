<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type', 50)->nullable()->after('file_path')->index();
            }
            if (!Schema::hasColumn('products', 'stock_tracking_type')) {
                $table->string('stock_tracking_type', 30)->default('quantity')->after('product_type')->index();
            }
            if (!Schema::hasColumn('products', 'cost_method')) {
                $table->string('cost_method', 30)->default('fifo')->after('stock_tracking_type');
            }
            if (!Schema::hasColumn('products', 'is_stock_managed')) {
                $table->boolean('is_stock_managed')->default(true)->after('cost_method');
            }
            if (!Schema::hasColumn('products', 'allow_decimal_qty')) {
                $table->boolean('allow_decimal_qty')->default(false)->after('is_stock_managed');
            }
            if (!Schema::hasColumn('products', 'has_expiry')) {
                $table->boolean('has_expiry')->default(false)->after('allow_decimal_qty');
            }
            if (!Schema::hasColumn('products', 'default_purchase_unit_id')) {
                $table->unsignedBigInteger('default_purchase_unit_id')->nullable()->after('unit_id')->index();
            }
            if (!Schema::hasColumn('products', 'default_sale_unit_id')) {
                $table->unsignedBigInteger('default_sale_unit_id')->nullable()->after('default_purchase_unit_id')->index();
            }
            if (!Schema::hasColumn('products', 'preparation_time_minutes')) {
                $table->unsignedInteger('preparation_time_minutes')->nullable()->after('has_expiry');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach ([
                'preparation_time_minutes',
                'default_sale_unit_id',
                'default_purchase_unit_id',
                'has_expiry',
                'allow_decimal_qty',
                'is_stock_managed',
                'cost_method',
                'stock_tracking_type',
            ] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

