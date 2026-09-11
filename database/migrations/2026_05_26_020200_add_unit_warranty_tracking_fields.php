<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_purchase_order_product_units')) {
            return;
        }

        Schema::table('product_purchase_order_product_units', function (Blueprint $table) {
            if (!Schema::hasColumn('product_purchase_order_product_units', 'serial_no')) {
                $table->string('serial_no', 100)->nullable()->after('code')->index();
            }
            if (!Schema::hasColumn('product_purchase_order_product_units', 'imei_1')) {
                $table->string('imei_1', 30)->nullable()->after('serial_no')->index();
            }
            if (!Schema::hasColumn('product_purchase_order_product_units', 'imei_2')) {
                $table->string('imei_2', 30)->nullable()->after('imei_1')->index();
            }
            if (!Schema::hasColumn('product_purchase_order_product_units', 'supplier_warranty_start_date')) {
                $table->date('supplier_warranty_start_date')->nullable()->after('imei_2');
            }
            if (!Schema::hasColumn('product_purchase_order_product_units', 'supplier_warranty_end_date')) {
                $table->date('supplier_warranty_end_date')->nullable()->after('supplier_warranty_start_date')->index('ppopu_supplier_warranty_end_idx');
            }
            if (!Schema::hasColumn('product_purchase_order_product_units', 'customer_warranty_start_date')) {
                $table->date('customer_warranty_start_date')->nullable()->after('supplier_warranty_end_date');
            }
            if (!Schema::hasColumn('product_purchase_order_product_units', 'customer_warranty_end_date')) {
                $table->date('customer_warranty_end_date')->nullable()->after('customer_warranty_start_date')->index('ppopu_customer_warranty_end_idx');
            }
            if (!Schema::hasColumn('product_purchase_order_product_units', 'warranty_note')) {
                $table->text('warranty_note')->nullable()->after('customer_warranty_end_date');
            }
            if (!Schema::hasColumn('product_purchase_order_product_units', 'extra_attributes')) {
                $table->json('extra_attributes')->nullable()->after('warranty_note');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_purchase_order_product_units')) {
            return;
        }

        Schema::table('product_purchase_order_product_units', function (Blueprint $table) {
            foreach ([
                'extra_attributes',
                'warranty_note',
                'customer_warranty_end_date',
                'customer_warranty_start_date',
                'supplier_warranty_end_date',
                'supplier_warranty_start_date',
                'imei_2',
                'imei_1',
                'serial_no',
            ] as $column) {
                if (Schema::hasColumn('product_purchase_order_product_units', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
