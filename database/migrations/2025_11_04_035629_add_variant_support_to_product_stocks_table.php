<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_stocks')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                if (!Schema::hasColumn('product_stocks', 'has_variant')) {
                    $table->boolean('has_variant')->default(false)->after('product_id')
                        ->comment('Flag to indicate if this stock is for a product variant');
                }
                if (!Schema::hasColumn('product_stocks', 'variant_combination_key')) {
                    $table->string('variant_combination_key', 255)->nullable()->after('has_variant')
                        ->comment('Variant combination key (e.g., Red-SM-Cotton)');
                }
                if (!Schema::hasColumn('product_stocks', 'variant_sku')) {
                    $table->string('variant_sku', 100)->nullable()->after('variant_combination_key')
                        ->comment('Variant SKU for identification');
                }
                if (!Schema::hasColumn('product_stocks', 'variant_barcode')) {
                    $table->string('variant_barcode', 50)->nullable()->after('variant_sku')
                        ->comment('Variant barcode');
                }
                if (!Schema::hasColumn('product_stocks', 'variant_data')) {
                    $table->json('variant_data')->nullable()->after('variant_barcode')
                        ->comment('JSON data containing variant attributes');
                }
                if (!Schema::hasColumn('product_stocks', 'variant_price')) {
                    $table->decimal('variant_price', 10, 2)->nullable()->after('variant_data')
                        ->comment('Variant specific price (overrides product price if set)');
                }
                if (!Schema::hasColumn('product_stocks', 'variant_discount_price')) {
                    $table->decimal('variant_discount_price', 10, 2)->nullable()->after('variant_price')
                        ->comment('Variant discount price');
                }
            });
        }

        if (Schema::hasTable('product_stock_logs')) {
            Schema::table('product_stock_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('product_stock_logs', 'has_variant')) {
                    $table->boolean('has_variant')->default(false)->after('product_id')
                        ->comment('Flag to indicate if this log is for a product variant');
                }
                if (!Schema::hasColumn('product_stock_logs', 'variant_combination_key')) {
                    $table->string('variant_combination_key', 255)->nullable()->after('has_variant')
                        ->comment('Variant combination key');
                }
                if (!Schema::hasColumn('product_stock_logs', 'variant_sku')) {
                    $table->string('variant_sku', 100)->nullable()->after('variant_combination_key')
                        ->comment('Variant SKU');
                }
                if (!Schema::hasColumn('product_stock_logs', 'variant_data')) {
                    $table->json('variant_data')->nullable()->after('variant_sku')
                        ->comment('JSON data containing variant attributes');
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: preserve stock and stock-log variant history.
    }
};
