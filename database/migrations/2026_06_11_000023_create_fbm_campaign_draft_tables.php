<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fbm_campaign_drafts')) {
            Schema::create('fbm_campaign_drafts', function (Blueprint $table) {
                $table->id();
                $table->uuid('draft_uuid')->unique();
                $table->unsignedBigInteger('fbm_connection_id')->nullable();
                $table->unsignedBigInteger('fbm_ad_account_id')->nullable();
                $table->unsignedBigInteger('fbm_page_id')->nullable();
                $table->string('draft_name', 255);
                $table->string('objective', 80);
                $table->json('special_ad_categories')->nullable();
                $table->string('budget_type', 40)->default('daily');
                $table->decimal('budget_amount', 14, 2)->default(0);
                $table->string('currency', 10)->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->string('optimization_goal', 120)->nullable();
                $table->string('billing_event', 120)->nullable();
                $table->string('destination_url', 1000)->nullable();
                $table->string('utm_source', 80)->nullable();
                $table->string('utm_medium', 80)->nullable();
                $table->string('utm_campaign', 160)->nullable();
                $table->text('notes')->nullable();
                $table->string('status', 40)->default('draft');
                $table->unsignedTinyInteger('approval_version')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('submitted_by')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('rejected_by')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at'], 'fbm_campaign_drafts_status_idx');
                $table->index(['fbm_ad_account_id', 'status'], 'fbm_campaign_drafts_account_idx');
            });
        }

        if (!Schema::hasTable('fbm_campaign_draft_assets')) {
            Schema::create('fbm_campaign_draft_assets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_campaign_draft_id');
                $table->string('asset_type', 40);
                $table->unsignedBigInteger('asset_id');
                $table->string('role', 80)->nullable();
                $table->json('snapshot')->nullable();
                $table->timestamps();

                $table->unique(['fbm_campaign_draft_id', 'asset_type', 'asset_id'], 'fbm_draft_asset_uq');
                $table->index(['asset_type', 'asset_id'], 'fbm_draft_asset_lookup_idx');
            });
        }

        if (!Schema::hasTable('fbm_campaign_draft_approvals')) {
            Schema::create('fbm_campaign_draft_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_campaign_draft_id');
                $table->string('action', 40);
                $table->string('from_status', 40)->nullable();
                $table->string('to_status', 40);
                $table->json('safe_metadata')->nullable();
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->string('comment', 500)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['fbm_campaign_draft_id', 'created_at'], 'fbm_draft_approvals_draft_idx');
            });
        }

        if (!Schema::hasTable('fbm_campaign_publish_snapshots')) {
            Schema::create('fbm_campaign_publish_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_campaign_draft_id');
                $table->uuid('snapshot_uuid')->unique();
                $table->unsignedTinyInteger('approval_version');
                $table->json('snapshot_payload');
                $table->string('payload_hash', 80);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->unique(['fbm_campaign_draft_id', 'approval_version'], 'fbm_publish_snapshot_version_uq');
            });
        }
    }

    public function down(): void
    {
        // Campaign draft governance records and approval snapshots are retained intentionally.
    }
};
