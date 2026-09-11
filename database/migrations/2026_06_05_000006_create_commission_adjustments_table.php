<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommissionAdjustmentsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('commission_adjustments')) {
            return;
        }

        Schema::create('commission_adjustments', function (Blueprint $table) {
            $table->id();
            $table->enum('adjustment_for', ['salesman', 'affiliate']);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('affiliate_id')->nullable();
            $table->unsignedBigInteger('product_order_id')->nullable();
            $table->unsignedBigInteger('sales_commission_entry_id')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('type', ['addition', 'deduction'])->default('deduction');
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'applied', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('commission_adjustments');
    }
}
