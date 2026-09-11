<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('srms_service_instances', function (Blueprint $table) {
            $table->id();
            $table->string('instance_no', 50)->unique();
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('customer_id');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('billing_unit_qty', 16, 4)->default(1);
            $table->decimal('service_unit_price', 16, 2)->default(0);
            $table->decimal('service_subtotal', 16, 2)->default(0);
            $table->decimal('products_subtotal', 16, 2)->default(0);
            $table->decimal('total_amount', 16, 2)->default(0);
            $table->decimal('paid_amount', 16, 2)->default(0);
            $table->decimal('due_amount', 16, 2)->default(0);
            $table->enum('status', ['draft', 'confirmed', 'billed', 'paid', 'cancelled'])->default('draft');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('billed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('rental_returned_at')->nullable();
            $table->timestamp('inventory_applied_at')->nullable();
            $table->timestamp('accounting_posted_at')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'status'], 'srms_instances_service_status_idx');
            $table->index(['customer_id', 'status'], 'srms_instances_customer_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('srms_service_instances');
    }
};
