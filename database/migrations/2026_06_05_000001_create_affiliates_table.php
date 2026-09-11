<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAffiliatesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('affiliates')) {
            return;
        }

        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('code', 80)->unique();
            $table->enum('default_commission_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('default_commission_value', 12, 2)->default(0);
            $table->enum('default_commission_base', ['sale_amount', 'gross_profit'])->default('sale_amount');
            $table->text('note')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('affiliates');
    }
}
