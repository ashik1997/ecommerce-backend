<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderExtraChargeTypesTable extends Migration
{
    public function up()
    {
        Schema::create('order_extra_charge_types', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->string('scope', 50)->default('sales');
            $table->unsignedBigInteger('creator')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_extra_charge_types');
    }
}
