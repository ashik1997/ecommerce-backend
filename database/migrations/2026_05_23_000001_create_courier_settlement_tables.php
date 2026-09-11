<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_code', 80)->unique();
            $table->string('courier', 50)->index();
            $table->date('settlement_date')->index();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->decimal('settlement_amount', 16, 2)->default(0);
            $table->decimal('receivable_total', 16, 2)->default(0);
            $table->decimal('courier_expense_total', 16, 2)->default(0);
            $table->decimal('received_total', 16, 2)->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('creator')->nullable();
            $table->string('status', 20)->default('posted');
            $table->timestamps();
        });

        Schema::create('courier_settlement_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('courier_settlement_id')->index();
            $table->unsignedBigInteger('product_order_id')->index();
            $table->string('order_code', 120)->nullable();
            $table->string('courier', 50)->index();
            $table->string('tracking_id', 120)->nullable();
            $table->string('raw_status', 120)->nullable();
            $table->string('normalized_status', 50)->index();
            $table->decimal('cod_amount', 16, 2)->default(0);
            $table->decimal('courier_cost', 16, 2)->default(0);
            $table->decimal('received_amount', 16, 2)->default(0);
            $table->decimal('returned_amount', 16, 2)->default(0);
            $table->decimal('restocked_qty', 16, 2)->default(0);
            $table->boolean('is_inventory_adjusted')->default(false);
            $table->boolean('is_accounting_posted')->default(false);
            $table->json('raw_response')->nullable();
            $table->json('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_settlement_orders');
        Schema::dropIfExists('courier_settlements');
    }
};
