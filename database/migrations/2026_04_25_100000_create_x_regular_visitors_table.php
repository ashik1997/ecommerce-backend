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
        Schema::create('x_regular_visitors', function (Blueprint $table) {
            $table->id();

            $table->string('ip_address', 100)->nullable();
            $table->string('user_agent', 100)->nullable();
            $table->string('referrer', 100)->nullable();
            $table->string('url', 100)->nullable();
            $table->string('page', 100)->nullable();
            $table->bigInteger('qty')->unsigned()->nullable();
            $table->string('device', 100)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('isp', 100)->nullable();
            $table->string('org', 100)->nullable();
            $table->string('as', 100)->nullable();
            $table->string('as_name', 100)->nullable();
            $table->string('as_domain', 100)->nullable();
            $table->string('as_route', 100)->nullable();
            $table->string('as_type', 100)->nullable();
            $table->string('as_regional', 100)->nullable();
            $table->string('as_region', 100)->nullable();
            $table->string('as_city', 100)->nullable();
            $table->string('as_zip', 100)->nullable();

            $table->timestamps();

            $table->index('created_at');
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('x_regular_visitors');
    }
};
