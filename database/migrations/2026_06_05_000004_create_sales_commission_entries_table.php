<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesCommissionEntriesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('sales_commission_entries')) {
            return;
        }

        Schema::create('sales_commission_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_order_id');
            $table->unsignedBigInteger('product_order_product_id')->nullable();
            $table->unsignedBigInteger('sales_commission_rule_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('affiliate_id')->nullable();
            $table->enum('commission_for', ['salesman', 'affiliate']);
            $table->enum('commission_base', ['sale_amount', 'gross_profit'])->default('sale_amount');
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->enum('commission_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('commission_value', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'settled', 'paid', 'partially_paid', 'reversed'])->default('pending');
            $table->string('source_status', 50)->nullable();
            $table->unsignedBigInteger('settlement_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['product_order_id', 'commission_for']);
            $table->index(['user_id', 'status']);
            $table->index(['affiliate_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_commission_entries');
    }
}
