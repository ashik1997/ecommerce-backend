<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfitCostSummaryFieldsToProductOrdersTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('product_orders')) {
            return;
        }

        Schema::table('product_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('product_orders', 'direct_expense_total')) {
                $table->decimal('direct_expense_total', 14, 2)->default(0)->after('commission_total');
            }
            if (! Schema::hasColumn('product_orders', 'estimated_expense_total')) {
                $table->decimal('estimated_expense_total', 14, 2)->default(0)->after('direct_expense_total');
            }
            if (! Schema::hasColumn('product_orders', 'contribution_profit')) {
                $table->decimal('contribution_profit', 14, 2)->default(0)->after('estimated_expense_total');
            }
            if (! Schema::hasColumn('product_orders', 'management_net_profit')) {
                $table->decimal('management_net_profit', 14, 2)->default(0)->after('contribution_profit');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('product_orders')) {
            return;
        }

        Schema::table('product_orders', function (Blueprint $table) {
            foreach (['direct_expense_total', 'estimated_expense_total', 'contribution_profit', 'management_net_profit'] as $column) {
                if (Schema::hasColumn('product_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
