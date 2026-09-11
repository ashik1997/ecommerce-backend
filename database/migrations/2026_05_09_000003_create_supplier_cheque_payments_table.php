<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplierChequePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('supplier_cheque_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('payment_type_id');
            $table->unsignedBigInteger('source_account_id');
            $table->unsignedBigInteger('account_head_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('cheque_number');
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('cheque_type')->nullable();
            $table->date('issue_date');
            $table->date('execution_date');
            $table->date('cleared_date')->nullable();
            $table->enum('status', ['pending', 'cleared', 'cancelled', 'bounced'])->default('pending');
            $table->string('attachment')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('purchase_payment_id')->nullable();
            $table->unsignedBigInteger('supplier_payment_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
            $table->index(['purchase_id', 'status']);
            $table->index(['execution_date', 'status']);
            $table->index(['cheque_number', 'source_account_id'], 'supplier_cheque_number_account_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('supplier_cheque_payments');
    }
}
