<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHratHolidaysTable extends Migration
{
    public function up()
    {
        Schema::create('hrat_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('title');
            $table->enum('type', ['public', 'company', 'special_working_day'])->default('company');
            $table->boolean('is_working_day_override')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrat_holidays');
    }
}
