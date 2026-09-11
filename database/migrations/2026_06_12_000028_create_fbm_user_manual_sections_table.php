<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fbm_user_manual_sections')) {
            return;
        }

        Schema::create('fbm_user_manual_sections', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10)->default('en');
            $table->string('section_key', 120);
            $table->string('title', 255);
            $table->text('body');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['locale', 'section_key'], 'fbm_manual_locale_section_uq');
            $table->index(['locale', 'is_active', 'sort_order'], 'fbm_manual_locale_active_sort_idx');
        });
    }

    public function down(): void
    {
        // Intentionally preserved so application-local operator manual edits are not
        // lost during rollback.
    }
};
