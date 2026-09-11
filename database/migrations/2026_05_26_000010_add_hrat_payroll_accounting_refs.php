<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddHratPayrollAccountingRefs extends Migration
{
    public function up()
    {
        if (Schema::hasTable('hrat_payrolls')) {
            Schema::table('hrat_payrolls', function (Blueprint $table) {
                if (!Schema::hasColumn('hrat_payrolls', 'expense_account_id')) {
                    $table->unsignedBigInteger('expense_account_id')->nullable()->after('finalized_at');
                }
                if (!Schema::hasColumn('hrat_payrolls', 'payable_account_id')) {
                    $table->unsignedBigInteger('payable_account_id')->nullable()->after('expense_account_id');
                }
                if (!Schema::hasColumn('hrat_payrolls', 'accounting_posted_at')) {
                    $table->dateTime('accounting_posted_at')->nullable()->after('payable_account_id');
                }
                if (!Schema::hasColumn('hrat_payrolls', 'accounting_reference')) {
                    $table->string('accounting_reference', 100)->nullable()->after('accounting_posted_at');
                }
                if (!Schema::hasColumn('hrat_payrolls', 'payment_type_id')) {
                    $table->unsignedBigInteger('payment_type_id')->nullable()->after('accounting_reference');
                }
                if (!Schema::hasColumn('hrat_payrolls', 'payment_account_id')) {
                    $table->unsignedBigInteger('payment_account_id')->nullable()->after('payment_type_id');
                }
                if (!Schema::hasColumn('hrat_payrolls', 'payment_posted_at')) {
                    $table->dateTime('payment_posted_at')->nullable()->after('payment_account_id');
                }
                if (!Schema::hasColumn('hrat_payrolls', 'payment_reference')) {
                    $table->string('payment_reference', 100)->nullable()->after('payment_posted_at');
                }
            });

            try {
                DB::statement("ALTER TABLE hrat_payrolls MODIFY status ENUM('draft','approved','finalized','paid') DEFAULT 'draft'");
            } catch (\Throwable $exception) {
                // Some database drivers do not support MySQL enum modification.
            }
        }

        if (Schema::hasTable('ac_transactions')) {
            Schema::table('ac_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('ac_transactions', 'ref_payroll_id')) {
                    $table->unsignedBigInteger('ref_payroll_id')->nullable()->after('ref_expense_id');
                }
                if (!Schema::hasColumn('ac_transactions', 'ref_payroll_line_id')) {
                    $table->unsignedBigInteger('ref_payroll_line_id')->nullable()->after('ref_payroll_id');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('ac_transactions')) {
            Schema::table('ac_transactions', function (Blueprint $table) {
                foreach (['ref_payroll_line_id', 'ref_payroll_id'] as $column) {
                    if (Schema::hasColumn('ac_transactions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('hrat_payrolls')) {
            Schema::table('hrat_payrolls', function (Blueprint $table) {
                foreach ([
                    'payment_reference',
                    'payment_posted_at',
                    'payment_account_id',
                    'payment_type_id',
                    'accounting_reference',
                    'accounting_posted_at',
                    'payable_account_id',
                    'expense_account_id',
                ] as $column) {
                    if (Schema::hasColumn('hrat_payrolls', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
}
