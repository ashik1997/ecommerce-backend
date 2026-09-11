<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDbExpensePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('db_expense_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_website_id')->nullable();
            $table->bigInteger('store_id')->nullable();
            $table->bigInteger('expense_id');
            $table->dateTime('payment_date')->nullable();
            $table->double('payment_amount', 20, 4)->default(0);
            $table->bigInteger('payment_type_id')->nullable();
            $table->bigInteger('account_id')->nullable();
            $table->text('payment_note')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->date('created_date')->nullable();
            $table->string('created_time', 100)->nullable();
            $table->string('system_ip', 100)->nullable();
            $table->string('system_name', 100)->nullable();
            $table->bigInteger('creator')->nullable();
            $table->string('slug', 192)->nullable();
            $table->enum('status', ['active', 'inactive', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index('expense_id', 'idx_expense_payments_expense_id');
            $table->index('account_id', 'idx_expense_payments_account_id');
            $table->index('payment_type_id', 'idx_expense_payments_payment_type_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('db_expense_payments');
    }
}
