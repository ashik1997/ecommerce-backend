<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductWebsitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_websites', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_website_id')->nullable();

            $table->string('title', 200)->nullable();
            $table->string('url', 200)->nullable();
            $table->string('logo', 200)->nullable();
            
            $table->unsignedBigInteger('creator')->nullable();
            $table->string('slug', 200)->nullable();
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
        Schema::dropIfExists('product_websites');
    }
}
