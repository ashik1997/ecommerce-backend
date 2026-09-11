<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommissionFieldsToProductOrdersTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('product_orders')) {
            return;
        }

        Schema::table('product_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('product_orders', 'salesman_id')) {
                $table->unsignedBigInteger('salesman_id')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('product_orders', 'affiliate_id')) {
                $table->unsignedBigInteger('affiliate_id')->nullable()->after('salesman_id');
            }
            if (! Schema::hasColumn('product_orders', 'affiliate_code')) {
                $table->string('affiliate_code', 80)->nullable()->after('affiliate_id');
            }
            if (! Schema::hasColumn('product_orders', 'commission_status')) {
                $table->string('commission_status', 30)->nullable()->default('pending')->after('affiliate_code');
            }
            if (! Schema::hasColumn('product_orders', 'commission_calculated_at')) {
                $table->timestamp('commission_calculated_at')->nullable()->after('commission_status');
            }
            if (! Schema::hasColumn('product_orders', 'commission_reversed_at')) {
                $table->timestamp('commission_reversed_at')->nullable()->after('commission_calculated_at');
            }
            if (! Schema::hasColumn('product_orders', 'salesman_commission_total')) {
                $table->decimal('salesman_commission_total', 12, 2)->default(0)->after('commission_reversed_at');
            }
            if (! Schema::hasColumn('product_orders', 'affiliate_commission_total')) {
                $table->decimal('affiliate_commission_total', 12, 2)->default(0)->after('salesman_commission_total');
            }
            if (! Schema::hasColumn('product_orders', 'commission_total')) {
                $table->decimal('commission_total', 12, 2)->default(0)->after('affiliate_commission_total');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('product_orders')) {
            return;
        }

        Schema::table('product_orders', function (Blueprint $table) {
            $columns = [
                'salesman_id', 'affiliate_id', 'affiliate_code', 'commission_status',
                'commission_calculated_at', 'commission_reversed_at',
                'salesman_commission_total', 'affiliate_commission_total', 'commission_total',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('product_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
