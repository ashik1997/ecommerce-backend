<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_stock_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('product_stock_logs', 'service_instance_id')) {
                $table->unsignedBigInteger('service_instance_id')->nullable()->after('product_return_id');
                $table->index('service_instance_id', 'psl_service_instance_idx');
            }

            if (!Schema::hasColumn('product_stock_logs', 'service_instance_product_id')) {
                $table->unsignedBigInteger('service_instance_product_id')->nullable()->after('service_instance_id');
                $table->index('service_instance_product_id', 'psl_service_instance_product_idx');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `product_stock_logs` MODIFY COLUMN `type` ENUM('sales','purchase','purchase_return','return','initial','transfer','waste','manual add','service_usage','rental_checkout','rental_return') NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `product_stock_logs` MODIFY COLUMN `type` ENUM('sales','purchase','purchase_return','return','initial','transfer','waste','manual add') NULL");
        }

        Schema::table('product_stock_logs', function (Blueprint $table) {
            if (Schema::hasColumn('product_stock_logs', 'service_instance_product_id')) {
                $table->dropIndex('psl_service_instance_product_idx');
                $table->dropColumn('service_instance_product_id');
            }

            if (Schema::hasColumn('product_stock_logs', 'service_instance_id')) {
                $table->dropIndex('psl_service_instance_idx');
                $table->dropColumn('service_instance_id');
            }
        });
    }
};
