<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('srms_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['rental', 'sale', 'mixed'])->default('sale');
            $table->decimal('base_price', 16, 2)->default(0);
            $table->enum('billing_unit', ['unit', 'meter', 'day', 'month'])->default('unit');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['name', 'type'], 'srms_services_name_type_unique');
            $table->index(['type', 'billing_unit'], 'srms_services_type_unit_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('srms_services');
    }
};
