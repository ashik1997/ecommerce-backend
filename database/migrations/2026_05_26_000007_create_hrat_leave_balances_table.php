<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHratLeaveBalancesTable extends Migration
{
    public function up()
    {
        Schema::create('hrat_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->unsignedSmallInteger('year');
            $table->decimal('opening_days', 8, 2)->default(0);
            $table->decimal('allocated_days', 8, 2)->default(0);
            $table->decimal('used_days', 8, 2)->default(0);
            $table->decimal('remaining_days', 8, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year'], 'hrat_leave_balance_unique');
            $table->index('employee_id');
            $table->index('leave_type_id');
            $table->index('year');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrat_leave_balances');
    }
}
