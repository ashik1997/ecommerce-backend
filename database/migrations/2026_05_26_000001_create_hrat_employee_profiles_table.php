<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHratEmployeeProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('hrat_employee_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('employee_code')->unique();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('department')->nullable();
            $table->string('designation')->nullable();
            $table->string('branch')->nullable();
            $table->enum('employment_type', ['permanent', 'probation', 'contract', 'part_time', 'intern'])->nullable();
            $table->date('joining_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->date('leaving_date')->nullable();
            $table->unsignedBigInteger('reporting_manager_user_id')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('present_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->enum('status', ['active', 'probation', 'resigned', 'terminated', 'suspended'])->default('active');
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('department_id');
            $table->index('designation_id');
            $table->index('branch_id');
            $table->index('department');
            $table->index('designation');
            $table->index('branch');
            $table->index('status');
            $table->index('reporting_manager_user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrat_employee_profiles');
    }
}
