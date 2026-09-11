<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductOrderRefundsTable extends Migration
{
    public function up()
    {
        Schema::create('product_order_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_website_id')->nullable();
            $table->unsignedBigInteger('store_id')->nullable();

            $table->unsignedBigInteger('product_order_return_id')->nullable();
            $table->unsignedBigInteger('product_order_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();

            $table->string('refund_code', 100)->nullable();
            $table->date('refund_date')->nullable();
            $table->decimal('refund_amount', 16, 2)->default(0);

            $table->unsignedBigInteger('payment_type_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable()->comment('Resolved from selected payment type');
            $table->string('payment_type_snapshot', 100)->nullable();

            $table->string('refund_status', 30)->default('completed');
            $table->text('note')->nullable();

            $table->tinyInteger('is_accounting_posted')->unsigned()->default(0);
            $table->timestamp('accounting_posted_at')->nullable();
            $table->string('accounting_reference', 100)->nullable();
            $table->unsignedBigInteger('reversal_refund_id')->nullable();

            $table->unsignedBigInteger('creator')->nullable();
            $table->string('slug')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('product_order_return_id', 'por_refunds_return_idx');
            $table->index('product_order_id', 'por_refunds_order_idx');
            $table->index('customer_id', 'por_refunds_customer_idx');
            $table->index('refund_code', 'por_refunds_code_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_order_refunds');
    }
}
