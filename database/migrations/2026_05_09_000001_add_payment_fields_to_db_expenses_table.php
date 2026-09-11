<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentFieldsToDbExpensesTable extends Migration
{
    public function up()
    {
        Schema::table('db_expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('db_expenses', 'tax_id')) {
                $table->unsignedBigInteger('tax_id')->nullable()->after('expense_for');
            }
            if (! Schema::hasColumn('db_expenses', 'tax_amount')) {
                $table->double('tax_amount', 20, 4)->default(0)->after('expense_amt');
            }
            if (! Schema::hasColumn('db_expenses', 'final_amount')) {
                $table->double('final_amount', 20, 4)->default(0)->after('tax_amount');
            }
            if (! Schema::hasColumn('db_expenses', 'paid_amount')) {
                $table->double('paid_amount', 20, 4)->default(0)->after('final_amount');
            }
            if (! Schema::hasColumn('db_expenses', 'due_amount')) {
                $table->double('due_amount', 20, 4)->default(0)->after('paid_amount');
            }
            if (! Schema::hasColumn('db_expenses', 'payment_status')) {
                $table->enum('payment_status', ['paid', 'partial', 'due', 'cancelled'])->default('due')->after('due_amount');
            }
            if (! Schema::hasColumn('db_expenses', 'expense_status')) {
                $table->enum('expense_status', ['resolved', 'unresolved', 'partial', 'cancelled'])->default('unresolved')->after('payment_status');
            }
            if (! Schema::hasColumn('db_expenses', 'paid_on')) {
                $table->dateTime('paid_on')->nullable()->after('account_id');
            }
            if (! Schema::hasColumn('db_expenses', 'payment_note')) {
                $table->text('payment_note')->nullable()->after('paid_on');
            }
        });
    }

    public function down()
    {
        Schema::table('db_expenses', function (Blueprint $table) {
            $columns = [
                'tax_id',
                'tax_amount',
                'final_amount',
                'paid_amount',
                'due_amount',
                'payment_status',
                'expense_status',
                'paid_on',
                'payment_note',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('db_expenses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
