<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->campaigns();
        $this->adSets();
        $this->ads();
        $this->creatives();
        $this->runItems();
    }

    public function down(): void
    {
        Schema::dropIfExists('fbm_sync_run_items');
        Schema::dropIfExists('fbm_creatives');
        Schema::dropIfExists('fbm_ads');
        Schema::dropIfExists('fbm_ad_sets');
        Schema::dropIfExists('fbm_campaigns');
    }

    private function campaigns(): void
    {
        if (Schema::hasTable('fbm_campaigns')) { return; }
        Schema::create('fbm_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fbm_connection_id');
            $table->unsignedBigInteger('fbm_ad_account_id');
            $table->string('provider_sync_key', 190);
            $table->string('name', 255)->nullable();
            $table->string('objective', 120)->nullable();
            $table->string('configured_status', 80)->nullable();
            $table->string('effective_status', 80)->nullable();
            $table->timestamp('provider_created_time')->nullable();
            $table->timestamp('provider_updated_time')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['fbm_connection_id','provider_sync_key'], 'fbm_campaign_provider_unique');
            $table->index(['fbm_ad_account_id','is_available'], 'fbm_campaign_account_available_idx');
        });
    }

    private function adSets(): void
    {
        if (Schema::hasTable('fbm_ad_sets')) { return; }
        Schema::create('fbm_ad_sets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fbm_connection_id');
            $table->unsignedBigInteger('fbm_ad_account_id');
            $table->unsignedBigInteger('fbm_campaign_id')->nullable();
            $table->string('provider_sync_key', 190);
            $table->string('name', 255)->nullable();
            $table->string('configured_status', 80)->nullable();
            $table->string('effective_status', 80)->nullable();
            $table->string('optimization_goal', 120)->nullable();
            $table->string('billing_event', 120)->nullable();
            $table->string('daily_budget', 80)->nullable();
            $table->string('lifetime_budget', 80)->nullable();
            $table->timestamp('provider_created_time')->nullable();
            $table->timestamp('provider_updated_time')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['fbm_connection_id','provider_sync_key'], 'fbm_adset_provider_unique');
            $table->index(['fbm_campaign_id','is_available'], 'fbm_adset_campaign_available_idx');
        });
    }

    private function ads(): void
    {
        if (Schema::hasTable('fbm_ads')) { return; }
        Schema::create('fbm_ads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fbm_connection_id');
            $table->unsignedBigInteger('fbm_ad_account_id');
            $table->unsignedBigInteger('fbm_campaign_id')->nullable();
            $table->unsignedBigInteger('fbm_ad_set_id')->nullable();
            $table->unsignedBigInteger('fbm_creative_id')->nullable();
            $table->string('provider_sync_key', 190);
            $table->string('provider_creative_sync_key', 190)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('configured_status', 80)->nullable();
            $table->string('effective_status', 80)->nullable();
            $table->timestamp('provider_created_time')->nullable();
            $table->timestamp('provider_updated_time')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['fbm_connection_id','provider_sync_key'], 'fbm_ad_provider_unique');
            $table->index(['fbm_ad_set_id','is_available'], 'fbm_ad_adset_available_idx');
        });
    }

    private function creatives(): void
    {
        if (Schema::hasTable('fbm_creatives')) { return; }
        Schema::create('fbm_creatives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fbm_connection_id');
            $table->unsignedBigInteger('fbm_ad_account_id');
            $table->string('provider_sync_key', 190);
            $table->string('name', 255)->nullable();
            $table->string('title', 255)->nullable();
            $table->string('body', 500)->nullable();
            $table->string('object_type', 80)->nullable();
            $table->string('thumbnail_url_hash', 64)->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['fbm_connection_id','provider_sync_key'], 'fbm_creative_provider_unique');
            $table->index(['fbm_ad_account_id','is_available'], 'fbm_creative_account_available_idx');
        });
    }

    private function runItems(): void
    {
        if (Schema::hasTable('fbm_sync_run_items')) { return; }
        Schema::create('fbm_sync_run_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fbm_sync_run_id')->nullable();
            $table->unsignedBigInteger('fbm_connection_id');
            $table->unsignedBigInteger('fbm_ad_account_id')->nullable();
            $table->string('family', 80);
            $table->string('status', 30);
            $table->unsignedInteger('seen_count')->default(0);
            $table->unsignedInteger('upserted_count')->default(0);
            $table->unsignedInteger('unavailable_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->string('redacted_message', 500)->nullable();
            $table->json('safe_summary')->nullable();
            $table->timestamps();
            $table->index(['fbm_sync_run_id','family'], 'fbm_sync_item_run_family_idx');
            $table->index(['fbm_connection_id','created_at'], 'fbm_sync_item_conn_created_idx');
        });
    }
};
