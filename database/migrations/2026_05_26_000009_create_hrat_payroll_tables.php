<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHratPayrollTables extends Migration
{
    public function up()
    {
        Schema::create('hrat_payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->date('from_date');
            $table->date('to_date');
            $table->enum('status', ['draft', 'approved', 'finalized', 'paid'])->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->unsignedBigInteger('expense_account_id')->nullable();
            $table->unsignedBigInteger('payable_account_id')->nullable();
            $table->dateTime('accounting_posted_at')->nullable();
            $table->string('accounting_reference', 100)->nullable();
            $table->unsignedBigInteger('payment_type_id')->nullable();
            $table->unsignedBigInteger('payment_account_id')->nullable();
            $table->dateTime('payment_posted_at')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['year', 'month'], 'hrat_payroll_year_month_unique');
        });

        Schema::create('hrat_payroll_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_id');
            $table->unsignedBigInteger('employee_id');
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('allowance_total', 15, 2)->default(0);
            $table->decimal('deduction_total', 15, 2)->default(0);
            $table->unsignedInteger('present_days')->default(0);
            $table->unsignedInteger('absent_days')->default(0);
            $table->unsignedInteger('late_days')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->decimal('absent_deduction', 15, 2)->default(0);
            $table->decimal('overtime_amount', 15, 2)->default(0);
            $table->decimal('net_payable', 15, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['payroll_id', 'employee_id'], 'hrat_payroll_line_unique');
            $table->index('employee_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrat_payroll_lines');
        Schema::dropIfExists('hrat_payrolls');
    }
}
