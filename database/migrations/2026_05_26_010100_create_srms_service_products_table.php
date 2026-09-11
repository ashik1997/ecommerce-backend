<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('srms_service_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity_required', 16, 4)->default(1);
            $table->decimal('default_unit_price', 16, 2)->nullable();
            $table->boolean('is_required')->default(false);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['service_id', 'product_id'], 'srms_service_products_unique');
            $table->index('product_id', 'srms_service_products_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('srms_service_products');
    }
};
