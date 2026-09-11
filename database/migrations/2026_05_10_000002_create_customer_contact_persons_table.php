<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_contact_persons')) {
            Schema::create('customer_contact_persons', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->index();
                $table->string('name');
                $table->string('designation')->nullable();
                $table->string('department')->nullable();
                $table->string('phone', 60)->nullable()->index();
                $table->string('email', 100)->nullable();
                $table->boolean('is_primary')->default(false);
                $table->text('note')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active')->index();
                $table->timestamps();

                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_contact_persons');
    }
};

