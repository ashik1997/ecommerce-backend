<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommissionSettlementsTables extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('commission_settlements')) {
            Schema::create('commission_settlements', function (Blueprint $table) {
                $table->id();
                $table->string('settlement_no')->unique();
                $table->enum('settlement_for', ['salesman', 'affiliate']);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('affiliate_id')->nullable();
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('total_commission', 12, 2)->default(0);
                $table->decimal('previous_due', 12, 2)->default(0);
                $table->decimal('adjustment_amount', 12, 2)->default(0);
                $table->decimal('payable_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('due_amount', 12, 2)->default(0);
                $table->unsignedBigInteger('payment_method_id')->nullable();
                $table->enum('status', ['draft', 'approved', 'paid', 'partially_paid', 'cancelled'])->default('draft');
                $table->unsignedBigInteger('settled_by')->nullable();
                $table->timestamp('settled_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commission_settlement_items')) {
            Schema::create('commission_settlement_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('commission_settlement_id');
                $table->unsignedBigInteger('sales_commission_entry_id');
                $table->decimal('commission_amount', 12, 2)->default(0);
                $table->decimal('settled_amount', 12, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('commission_settlement_items');
        Schema::dropIfExists('commission_settlements');
    }
}
