<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_offers', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('background_image')->nullable();
            $table->text('custom_style')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->string('slug', 100)->nullable();
            $table->unsignedBigInteger('creator')->nullable();
            $table->timestamps();
        });

        Schema::create('product_offer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_offer_id')->constrained('product_offers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->unsignedInteger('discount_percent')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->string('slug', 100)->nullable();
            $table->unsignedBigInteger('creator')->nullable();
            $table->timestamps();

            $table->unique(['product_offer_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_offer_items');
        Schema::dropIfExists('product_offers');
    }
};
