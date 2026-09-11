<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('breaking_news', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->integer('serial')->nullable();
            $table->string('url', 2048)->nullable();
            $table->tinyInteger('status')->default(1)->comment('0 inactive, 1 active');
            $table->string('creator')->nullable();
            $table->string('slug')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('breaking_news');
    }
};
