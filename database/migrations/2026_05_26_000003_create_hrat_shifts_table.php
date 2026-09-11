<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHratShiftsTable extends Migration
{
    public function up()
    {
        Schema::create('hrat_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->time('office_entry_time');
            $table->time('entry_safe_time');
            $table->time('office_exit_time');
            $table->time('early_exit_safe_time');
            $table->unsignedInteger('overtime_after_minutes')->default(0);
            $table->enum('overtime_calculation_method', ['from_exit_time', 'from_threshold_time'])->default('from_exit_time');
            $table->enum('late_calculation_method', ['from_entry_time', 'from_safe_time'])->default('from_entry_time');
            $table->time('entry_button_start_time');
            $table->time('entry_button_end_time');
            $table->time('exit_button_start_time');
            $table->time('exit_button_end_time');
            $table->json('working_days')->nullable();
            $table->json('weekly_holidays')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('is_default');
            $table->index('is_active');
        });

        Schema::table('hrat_employee_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->after('attendance_config_id');
            $table->index('shift_id', 'hrat_employee_schedules_shift_id_idx');
        });
    }

    public function down()
    {
        Schema::table('hrat_employee_schedules', function (Blueprint $table) {
            $table->dropIndex('hrat_employee_schedules_shift_id_idx');
            $table->dropColumn('shift_id');
        });

        Schema::dropIfExists('hrat_shifts');
    }
}
