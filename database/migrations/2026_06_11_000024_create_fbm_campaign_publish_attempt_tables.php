<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fbm_campaign_publish_attempts')) {
            Schema::create('fbm_campaign_publish_attempts', function (Blueprint $table) {
                $table->id();
                $table->uuid('attempt_uuid')->unique();
                $table->unsignedBigInteger('fbm_campaign_draft_id');
                $table->unsignedBigInteger('fbm_campaign_publish_snapshot_id');
                $table->string('idempotency_key', 80);
                $table->string('status', 40)->default('queued');
                $table->string('execution_mode', 40)->default('provider_writes_disabled');
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->unsignedBigInteger('provider_campaign_local_id')->nullable();
                $table->unsignedBigInteger('provider_ad_set_local_id')->nullable();
                $table->unsignedBigInteger('provider_creative_local_id')->nullable();
                $table->unsignedBigInteger('provider_ad_local_id')->nullable();
                $table->json('safe_response_summary')->nullable();
                $table->string('redacted_message', 500)->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['fbm_campaign_draft_id', 'fbm_campaign_publish_snapshot_id', 'idempotency_key'], 'fbm_publish_attempt_idem_uq');
                $table->index(['status', 'created_at'], 'fbm_publish_attempt_status_idx');
            });
        }

        if (!Schema::hasTable('fbm_campaign_publish_steps')) {
            Schema::create('fbm_campaign_publish_steps', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_campaign_publish_attempt_id');
                $table->string('step_key', 40);
                $table->unsignedTinyInteger('step_order');
                $table->string('status', 40)->default('pending');
                $table->string('http_method', 10)->nullable();
                $table->string('graph_edge', 190)->nullable();
                $table->unsignedSmallInteger('http_status')->nullable();
                $table->string('provider_response_ref', 190)->nullable();
                $table->string('provider_error_code', 80)->nullable();
                $table->string('provider_error_subcode', 80)->nullable();
                $table->string('redacted_message', 500)->nullable();
                $table->json('safe_request_summary')->nullable();
                $table->json('safe_response_summary')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['fbm_campaign_publish_attempt_id', 'step_key'], 'fbm_publish_step_uq');
                $table->index(['step_key', 'status'], 'fbm_publish_step_status_idx');
            });
        }
    }

    public function down(): void
    {
        // Publish ledgers are retained intentionally for audit and idempotency.
    }
};
