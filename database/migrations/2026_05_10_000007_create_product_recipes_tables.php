<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_recipes')) {
            Schema::create('product_recipes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->index();
                $table->decimal('yield_qty', 16, 4)->default(1);
                $table->string('yield_unit', 50)->nullable();
                $table->decimal('wastage_percent', 8, 4)->default(0);
                $table->decimal('preparation_cost', 16, 4)->default(0);
                $table->text('instructions')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active')->index();
                $table->timestamps();

                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('product_recipe_items')) {
            Schema::create('product_recipe_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_recipe_id')->index();
                $table->unsignedBigInteger('ingredient_product_id')->index();
                $table->unsignedBigInteger('variant_id')->nullable()->index();
                $table->decimal('qty', 16, 4)->default(0);
                $table->string('unit', 50)->nullable();
                $table->string('cost_method', 30)->nullable();
                $table->decimal('wastage_percent', 8, 4)->default(0);
                $table->boolean('is_optional')->default(false);
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('product_recipe_id')->references('id')->on('product_recipes')->onDelete('cascade');
                $table->foreign('ingredient_product_id')->references('id')->on('products')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recipe_items');
        Schema::dropIfExists('product_recipes');
    }
};

