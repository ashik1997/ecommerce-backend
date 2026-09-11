<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdvanceSourceToDbSupplierPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('db_supplier_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('db_supplier_payments', 'advance_opening_balance_id')) {
                $table->unsignedBigInteger('advance_opening_balance_id')->nullable()->after('supplier_opening_balance_id');
                $table->index('advance_opening_balance_id', 'dsp_adv_opening_id_idx');
            }
        });
    }

    public function down()
    {
        Schema::table('db_supplier_payments', function (Blueprint $table) {
            if (Schema::hasColumn('db_supplier_payments', 'advance_opening_balance_id')) {
                $table->dropIndex('dsp_adv_opening_id_idx');
                $table->dropColumn('advance_opening_balance_id');
            }
        });
    }
}
