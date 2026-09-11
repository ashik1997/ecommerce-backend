<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesCommissionRulesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('sales_commission_rules')) {
            return;
        }

        Schema::create('sales_commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name');
            $table->enum('commission_for', ['salesman', 'affiliate']);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('affiliate_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('website_id')->nullable();
            $table->enum('commission_base', ['sale_amount', 'gross_profit'])->default('sale_amount');
            $table->enum('commission_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('commission_value', 12, 2)->default(0);
            $table->decimal('min_order_amount', 12, 2)->default(0);
            $table->unsignedInteger('priority')->default(100);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_commission_rules');
    }
}
