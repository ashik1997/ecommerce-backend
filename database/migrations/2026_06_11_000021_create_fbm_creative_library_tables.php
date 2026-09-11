<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fbm_creative_assets')) {
            Schema::create('fbm_creative_assets', function (Blueprint $table) {
                $table->id();
                $table->uuid('asset_uuid')->unique();
                $table->string('asset_type', 40)->default('image');
                $table->string('title', 180);
                $table->string('primary_text', 1000)->nullable();
                $table->string('headline', 255)->nullable();
                $table->string('description', 500)->nullable();
                $table->string('call_to_action', 80)->nullable();
                $table->unsignedBigInteger('media_file_id')->nullable();
                $table->string('external_asset_url', 1000)->nullable();
                $table->string('landing_url', 1000)->nullable();
                $table->string('utm_source', 80)->nullable();
                $table->string('utm_medium', 80)->nullable();
                $table->string('utm_campaign', 160)->nullable();
                $table->string('status', 30)->default('draft');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['asset_type', 'status'], 'fbm_creative_assets_type_status_idx');
                $table->index('media_file_id', 'fbm_creative_assets_media_idx');
            });
        }

        if (!Schema::hasTable('fbm_creative_asset_variants')) {
            Schema::create('fbm_creative_asset_variants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_creative_asset_id');
                $table->string('variant_name', 120);
                $table->string('primary_text', 1000)->nullable();
                $table->string('headline', 255)->nullable();
                $table->string('description', 500)->nullable();
                $table->unsignedBigInteger('media_file_id')->nullable();
                $table->string('external_asset_url', 1000)->nullable();
                $table->string('status', 30)->default('draft');
                $table->timestamps();

                $table->index('fbm_creative_asset_id', 'fbm_creative_variants_asset_idx');
            });
        }

        if (!Schema::hasTable('fbm_creative_preflight_checks')) {
            Schema::create('fbm_creative_preflight_checks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_creative_asset_id');
                $table->string('status', 30);
                $table->unsignedSmallInteger('issue_count')->default(0);
                $table->json('checks')->nullable();
                $table->unsignedBigInteger('checked_by')->nullable();
                $table->timestamp('checked_at');

                $table->index(['fbm_creative_asset_id', 'checked_at'], 'fbm_creative_preflight_asset_idx');
            });
        }
    }

    public function down(): void
    {
        // Local creative draft/preflight records are retained intentionally.
    }
};
