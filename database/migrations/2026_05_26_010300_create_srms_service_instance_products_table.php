<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('srms_service_instance_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_instance_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity_used', 16, 4)->default(1);
            $table->decimal('unit_price', 16, 2)->default(0);
            $table->decimal('total_price', 16, 2)->default(0);
            $table->boolean('is_required')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('service_instance_id', 'srms_instance_products_instance_idx');
            $table->index('product_id', 'srms_instance_products_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('srms_service_instance_products');
    }
};
