<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAreaBaseCouriersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('area_base_couriers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('area_base_courier_id');
            $table->string('area_name')->nullable();
            $table->decimal('shipping_cost', 10, 2);
            $table->string('slug')->unique();
            $table->unsignedBigInteger('creator')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();

            $table->foreign('area_base_courier_id')->references('id')->on('area_base_courier_names')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('area_base_couriers');
    }
}
