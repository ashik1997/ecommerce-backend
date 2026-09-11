<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('srms_service_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no', 50)->unique();
            $table->unsignedBigInteger('service_instance_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('payment_type_id');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->decimal('amount', 16, 2);
            $table->date('payment_date');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['service_instance_id', 'payment_date'], 'srms_service_payments_instance_date_idx');
            $table->index(['customer_id', 'payment_date'], 'srms_service_payments_customer_date_idx');
            $table->index('payment_type_id', 'srms_service_payments_payment_type_idx');
            $table->index('account_id', 'srms_service_payments_account_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('srms_service_payments');
    }
};
