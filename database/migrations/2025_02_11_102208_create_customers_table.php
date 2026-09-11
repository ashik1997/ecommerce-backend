<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_website_id')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('customer_category_id')->nullable();
            $table->unsignedBigInteger('customer_source_type_id')->nullable();
            $table->unsignedBigInteger('reference_by')->nullable();
            $table->string('name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('phone', 60)->nullable();
            $table->string('phone_original', 60)->nullable();
            $table->string('email', 100)->nullable();
            $table->unsignedFloat('due')->nullable();
            $table->unsignedFloat('paid')->nullable();
            $table->unsignedFloat('balance')->nullable();
            $table->timestamp('last_buy')->nullable();
            $table->timestamp('last_transaction')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->string('thana')->nullable();
            $table->string('post_code', 20)->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('order_id')->nullable();
            $table->decimal('available_advance', 10, 2)->default(0);
            $table->text('info')->nullable();

            $table->unsignedBigInteger('creator')->nullable();
            $table->string('slug')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
