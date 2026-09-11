<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_purchase_orders')) {
            if (!Schema::hasColumn('product_purchase_orders', 'subtotal')) {
                Schema::table('product_purchase_orders', function (Blueprint $table) {
                    $table->decimal('subtotal', 10, 2)->nullable()->after('round_off');
                });
            }

            if (Schema::hasColumn('product_purchase_orders', 'other_charge_type')) {
                DB::statement('ALTER TABLE `product_purchase_orders` MODIFY COLUMN `other_charge_type` TEXT NULL');
            }
        }

        if (Schema::hasTable('product_purchase_order_products') && !Schema::hasColumn('product_purchase_order_products', 'barcodes')) {
            Schema::table('product_purchase_order_products', function (Blueprint $table) {
                $table->longText('barcodes')->nullable()->after('previous_stock');
            });
        }

        if (Schema::hasTable('product_purchase_order_product_units') && Schema::hasColumn('product_purchase_order_product_units', 'unit_status')) {
            DB::statement("ALTER TABLE `product_purchase_order_product_units` MODIFY COLUMN `unit_status` ENUM('pending','instock','sold','returned','lost','damaged') NULL DEFAULT 'instock' COMMENT 'pending=>Waiting for purchase receive; instock=>In stock; sold=>Sold; returned=>Returned; lost=>Lost; damaged=>Damaged'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_purchase_order_product_units') && Schema::hasColumn('product_purchase_order_product_units', 'unit_status')) {
            DB::statement("UPDATE `product_purchase_order_product_units` SET `unit_status` = 'instock' WHERE `unit_status` = 'pending'");
            DB::statement("ALTER TABLE `product_purchase_order_product_units` MODIFY COLUMN `unit_status` ENUM('instock','sold','returned','lost','damaged') NULL DEFAULT 'instock' COMMENT 'instock=>In stock; sold=>Sold; returned=>Returned; lost=>Lost; damaged=>Damaged'");
        }

        if (Schema::hasTable('product_purchase_order_products') && Schema::hasColumn('product_purchase_order_products', 'barcodes')) {
            Schema::table('product_purchase_order_products', function (Blueprint $table) {
                $table->dropColumn('barcodes');
            });
        }

        if (Schema::hasTable('product_purchase_orders')) {
            if (Schema::hasColumn('product_purchase_orders', 'subtotal')) {
                Schema::table('product_purchase_orders', function (Blueprint $table) {
                    $table->dropColumn('subtotal');
                });
            }

            if (Schema::hasColumn('product_purchase_orders', 'other_charge_type')) {
                DB::statement('ALTER TABLE `product_purchase_orders` MODIFY COLUMN `other_charge_type` VARCHAR(100) NULL');
            }
        }
    }
};
