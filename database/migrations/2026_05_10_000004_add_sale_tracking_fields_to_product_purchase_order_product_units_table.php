<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_purchase_order_product_units', function (Blueprint $table) {
            if (!Schema::hasColumn('product_purchase_order_product_units', 'product_warehouse_id')) {
                $table->unsignedBigInteger('product_warehouse_id')->nullable()->after('product_website_id')->index();
            }
            if (!Schema::hasColumn('product_purchase_order_product_units', 'sale_id')) {
                $table->unsignedBigInteger('sale_id')->nullable()->after('unit_status')->index()->comment('product_orders.id');
            }
            if (!Schema::hasColumn('product_purchase_order_product_units', 'return_id')) {
                $table->unsignedBigInteger('return_id')->nullable()->after('sale_id')->index()->comment('return id');
            }
            if (!Schema::hasColumn('product_purchase_order_product_units', 'product_order_product_id')) {
                $table->unsignedBigInteger('product_order_product_id')->nullable()->after('return_id')->index();
            }
            if (!Schema::hasColumn('product_purchase_order_product_units', 'batch_no')) {
                $table->string('batch_no', 100)->nullable()->after('code')->index();
            }
            if (!Schema::hasColumn('product_purchase_order_product_units', 'manufacture_date')) {
                $table->date('manufacture_date')->nullable()->after('batch_no');
            }
            if (!Schema::hasColumn('product_purchase_order_product_units', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('manufacture_date')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_purchase_order_product_units', function (Blueprint $table) {
            foreach ([
                'expiry_date',
                'manufacture_date',
                'batch_no',
                'product_order_product_id',
                'return_id',
                'sale_id',
                'product_warehouse_id',
            ] as $column) {
                if (Schema::hasColumn('product_purchase_order_product_units', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

