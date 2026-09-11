<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fbm_module_settings')
            || Schema::hasColumn('fbm_module_settings', 'browser_pixel_mode')) {
            return;
        }

        Schema::table('fbm_module_settings', function (Blueprint $table) {
            $table->string('browser_pixel_mode', 20)->default('disabled');
        });
    }

    public function down(): void
    {
        // The operator-selected browser tracking mode is intentionally preserved.
        // Dropping it on rollback could silently change the application's consent-aware
        // storefront behavior after a later re-deploy.
    }
};
