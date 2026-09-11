<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureRules();
        $this->ensureRecommendations();
    }

    public function down(): void
    {
        // Intentionally non-destructive. Recommendation decisions are operator
        // audit records and require an explicit retention/removal procedure.
    }

    private function ensureRules(): void
    {
        if (Schema::hasTable('fbm_recommendation_rules')) {
            return;
        }

        Schema::create('fbm_recommendation_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('rule_uuid')->unique();
            $table->string('rule_key', 100)->unique();
            $table->string('rule_type', 80);
            $table->string('title', 255);
            $table->string('severity', 20)->default('info');
            $table->string('status', 20)->default('active');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->string('recommended_action_type', 40)->nullable();
            $table->string('recommended_target_type', 40)->nullable();
            $table->json('safe_conditions')->nullable();
            $table->string('redacted_message', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['rule_type', 'is_enabled'], 'fbm_rec_rule_type_enabled_idx');
        });
    }

    private function ensureRecommendations(): void
    {
        if (Schema::hasTable('fbm_recommendations')) {
            return;
        }

        Schema::create('fbm_recommendations', function (Blueprint $table) {
            $table->id();
            $table->uuid('recommendation_uuid')->unique();
            $table->char('dedupe_key', 64)->unique();
            $table->unsignedBigInteger('fbm_recommendation_rule_id')->nullable();
            $table->unsignedBigInteger('fbm_alert_id')->nullable();
            $table->string('recommendation_type', 80);
            $table->string('severity', 20)->default('info');
            $table->string('status', 30)->default('suggested');
            $table->string('source_type', 80)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('title', 255);
            $table->string('recommended_action_type', 40)->nullable();
            $table->string('recommended_target_type', 40)->nullable();
            $table->json('safe_context')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('dismissed_by')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->string('decision_note', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'severity'], 'fbm_rec_status_severity_idx');
            $table->index(['recommendation_type', 'last_seen_at'], 'fbm_rec_type_seen_idx');
        });
    }
};
