<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderProfitCostsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('order_profit_costs')) {
            return;
        }

        Schema::create('order_profit_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_order_id');
            $table->string('cost_type', 80);
            $table->string('cost_group', 50)->default('direct');
            $table->decimal('amount', 14, 2)->default(0);
            $table->boolean('is_estimated')->default(false);
            $table->string('status', 30)->default('active');
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['product_order_id', 'status']);
            $table->index(['cost_type', 'status']);
            $table->index(['source_type', 'source_id']);
            $table->unique(['source_type', 'source_id', 'cost_type'], 'op_cost_source_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_profit_costs');
    }
}
