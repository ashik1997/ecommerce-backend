<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHratAttendanceTables extends Migration
{
    public function up()
    {
        Schema::create('hrat_attendance_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->time('office_entry_time')->default('10:00:00');
            $table->time('entry_safe_time')->default('10:15:00');
            $table->time('office_exit_time')->default('19:00:00');
            $table->time('early_exit_safe_time')->default('18:55:00');
            $table->unsignedInteger('overtime_after_minutes')->default(30);
            $table->enum('overtime_calculation_method', ['from_exit_time', 'from_threshold_time'])->default('from_exit_time');
            $table->enum('late_calculation_method', ['from_entry_time', 'from_safe_time'])->default('from_entry_time');
            $table->unsignedTinyInteger('month_start_day')->default(1);
            $table->unsignedTinyInteger('month_end_day')->nullable();
            $table->time('entry_button_start_time')->default('09:45:00');
            $table->time('entry_button_end_time')->default('10:15:00');
            $table->time('exit_button_start_time')->default('18:50:00');
            $table->time('exit_button_end_time')->default('19:30:00');
            $table->json('working_days')->nullable();
            $table->json('weekly_holidays')->nullable();
            $table->string('timezone')->default('Asia/Dhaka');
            $table->boolean('allow_duplicate_employee_panel_entry')->default(false);
            $table->boolean('allow_exit_without_entry')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('hrat_employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('attendance_config_id')->nullable();
            $table->time('office_entry_time')->nullable();
            $table->time('entry_safe_time')->nullable();
            $table->time('office_exit_time')->nullable();
            $table->time('early_exit_safe_time')->nullable();
            $table->unsignedInteger('overtime_after_minutes')->nullable();
            $table->enum('overtime_calculation_method', ['from_exit_time', 'from_threshold_time'])->nullable();
            $table->enum('late_calculation_method', ['from_entry_time', 'from_safe_time'])->nullable();
            $table->time('entry_button_start_time')->nullable();
            $table->time('entry_button_end_time')->nullable();
            $table->time('exit_button_start_time')->nullable();
            $table->time('exit_button_end_time')->nullable();
            $table->json('working_days')->nullable();
            $table->json('weekly_holidays')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from', 'effective_to'], 'hrat_sched_emp_effective_idx');
        });

        Schema::create('hrat_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->string('source')->default('csv');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('success_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });

        Schema::create('hrat_attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('employee_code')->nullable();
            $table->date('attendance_date');
            $table->time('attendance_time');
            $table->dateTime('attendance_datetime');
            $table->enum('punch_type', ['entry', 'exit']);
            $table->enum('source', ['manual', 'csv', 'employee_panel', 'machine', 'api'])->default('manual');
            $table->string('device_id')->nullable();
            $table->unsignedBigInteger('import_batch_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_manual_adjusted')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_datetime', 'punch_type', 'source'], 'hrat_log_duplicate_guard');
            $table->index('employee_id');
            $table->index('attendance_date');
            $table->index('attendance_datetime');
            $table->index('punch_type');
            $table->index('source');
            $table->index('import_batch_id');
            $table->index(['employee_id', 'attendance_date'], 'hrat_log_emp_date_idx');
            $table->index(['employee_id', 'attendance_datetime'], 'hrat_log_emp_datetime_idx');
        });

        Schema::create('hrat_daily_attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('attendance_date');
            $table->time('first_entry_time')->nullable();
            $table->time('last_exit_time')->nullable();
            $table->unsignedInteger('total_entry_count')->default(0);
            $table->unsignedInteger('total_exit_count')->default(0);
            $table->unsignedInteger('gross_working_minutes')->default(0);
            $table->unsignedInteger('break_minutes')->default(0);
            $table->unsignedInteger('net_working_minutes')->default(0);
            $table->boolean('is_present')->default(false);
            $table->boolean('is_absent')->default(false);
            $table->boolean('is_late')->default(false);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->boolean('is_early_exit')->default(false);
            $table->unsignedInteger('early_exit_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->string('status')->default('absent');
            $table->string('source_summary')->nullable();
            $table->json('schedule_snapshot')->nullable();
            $table->text('remarks')->nullable();
            $table->dateTime('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date'], 'hrat_summary_employee_date_unique');
            $table->index('employee_id');
            $table->index('attendance_date');
            $table->index('status');
        });

        Schema::create('hrat_import_failed_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_batch_id');
            $table->unsignedInteger('row_number');
            $table->json('row_data')->nullable();
            $table->text('error_message');
            $table->timestamps();
            $table->index('import_batch_id');
        });

        Schema::create('hrat_salary_grades', function (Blueprint $table) {
            $table->id();
            $table->string('grade_name');
            $table->string('grade_code')->nullable();
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('hrat_employee_salary_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('salary_grade_id');
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'effective_from', 'effective_to'], 'hrat_salary_emp_effective_idx');
        });

        Schema::create('hrat_attendance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('attendance_date');
            $table->unsignedBigInteger('attendance_log_id')->nullable();
            $table->unsignedBigInteger('summary_id')->nullable();
            $table->enum('adjustment_type', ['create', 'update', 'delete', 'regenerate']);
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->text('reason');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'attendance_date'], 'hrat_adjust_emp_date_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrat_attendance_adjustments');
        Schema::dropIfExists('hrat_employee_salary_assignments');
        Schema::dropIfExists('hrat_salary_grades');
        Schema::dropIfExists('hrat_import_failed_rows');
        Schema::dropIfExists('hrat_daily_attendance_summaries');
        Schema::dropIfExists('hrat_attendance_logs');
        Schema::dropIfExists('hrat_import_batches');
        Schema::dropIfExists('hrat_employee_schedules');
        Schema::dropIfExists('hrat_attendance_configs');
    }
}
