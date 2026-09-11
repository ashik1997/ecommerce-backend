<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountingFieldsToCommissionSettlementsTable extends Migration
{
    public function up()
    {
        Schema::table('commission_settlements', function (Blueprint $table) {
            if (! Schema::hasColumn('commission_settlements', 'accounting_transaction_id')) {
                $table->unsignedBigInteger('accounting_transaction_id')->nullable()->after('payment_method_id');
            }
            if (! Schema::hasColumn('commission_settlements', 'payment_transaction_id')) {
                $table->unsignedBigInteger('payment_transaction_id')->nullable()->after('accounting_transaction_id');
            }
            if (! Schema::hasColumn('commission_settlements', 'accounting_status')) {
                $table->string('accounting_status', 40)->default('pending')->after('payment_transaction_id');
            }
        });
    }

    public function down()
    {
        Schema::table('commission_settlements', function (Blueprint $table) {
            if (Schema::hasColumn('commission_settlements', 'accounting_status')) {
                $table->dropColumn('accounting_status');
            }
            if (Schema::hasColumn('commission_settlements', 'payment_transaction_id')) {
                $table->dropColumn('payment_transaction_id');
            }
            if (Schema::hasColumn('commission_settlements', 'accounting_transaction_id')) {
                $table->dropColumn('accounting_transaction_id');
            }
        });
    }
}
