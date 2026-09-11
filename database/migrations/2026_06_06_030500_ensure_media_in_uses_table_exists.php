<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('media_in_uses')) {
            return;
        }

        Schema::create('media_in_uses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_website_id')->nullable();
            $table->unsignedBigInteger('media_id')->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('col_name', 100)->nullable();
            $table->unsignedBigInteger('creator')->nullable();
            $table->string('slug', 50)->nullable();
            $table->tinyInteger('status')->unsigned()->default(1);
            $table->timestamps();

            $table->index('media_id');
            $table->index(['model', 'model_id']);
            $table->index('slug');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_in_uses');
    }
};
