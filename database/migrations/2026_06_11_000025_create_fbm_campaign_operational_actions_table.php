<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fbm_campaign_operational_actions')) {
            Schema::create('fbm_campaign_operational_actions', function (Blueprint $table) {
                $table->id();
                $table->uuid('action_uuid')->unique();
                $table->unsignedBigInteger('fbm_campaign_draft_id');
                $table->unsignedBigInteger('fbm_campaign_publish_attempt_id')->nullable();
                $table->string('idempotency_key', 80);
                $table->string('action_type', 40);
                $table->string('target_type', 40);
                $table->unsignedBigInteger('target_local_id')->nullable();
                $table->string('status', 40)->default('queued');
                $table->string('execution_mode', 40)->default('provider_writes_disabled');
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->decimal('budget_amount', 14, 2)->nullable();
                $table->string('budget_type', 40)->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->string('reason', 500)->nullable();
                $table->string('redacted_message', 500)->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique('idempotency_key', 'fbm_operational_action_idem_uq');
                $table->index(['fbm_campaign_draft_id', 'created_at'], 'fbm_operational_action_draft_idx');
                $table->index(['action_type', 'status'], 'fbm_operational_action_status_idx');
            });
        }
    }

    public function down(): void
    {
        // Operational action ledgers are retained intentionally for audit and idempotency.
    }
};
