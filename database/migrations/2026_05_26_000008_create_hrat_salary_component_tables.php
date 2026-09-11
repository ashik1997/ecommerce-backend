<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHratSalaryComponentTables extends Migration
{
    public function up()
    {
        Schema::create('hrat_salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->enum('type', ['allowance', 'deduction']);
            $table->enum('calculation_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->boolean('is_taxable')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('calculation_type');
            $table->index('is_active');
        });

        Schema::create('hrat_employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_salary_assignment_id');
            $table->unsignedBigInteger('salary_component_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['employee_salary_assignment_id', 'salary_component_id'], 'hrat_emp_salary_component_unique');
            $table->index('employee_salary_assignment_id', 'hrat_emp_salary_component_assignment_idx');
            $table->index('salary_component_id', 'hrat_emp_salary_component_component_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrat_employee_salary_components');
        Schema::dropIfExists('hrat_salary_components');
    }
}
