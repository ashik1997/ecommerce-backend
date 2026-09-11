<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureWebhookLogs();
        $this->ensureReconciliationRuns();
        $this->ensureAlerts();
    }

    public function down(): void
    {
        // Intentionally non-destructive. Webhook evidence, reconciliation runs
        // and operator alerts require an explicit removal procedure.
    }

    private function ensureWebhookLogs(): void
    {
        if (Schema::hasTable('fbm_ad_account_webhook_logs')) {
            return;
        }

        Schema::create('fbm_ad_account_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->unsignedBigInteger('fbm_connection_id')->nullable();
            $table->unsignedBigInteger('fbm_ad_account_id')->nullable();
            $table->string('event_type', 40);
            $table->string('object_type', 60)->nullable();
            $table->string('provider_object_id', 190)->nullable();
            $table->string('change_field', 120)->nullable();
            $table->string('status', 30);
            $table->json('safe_change_summary')->nullable();
            $table->string('redacted_message', 500)->nullable();
            $table->char('request_fingerprint', 64);
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['fbm_connection_id', 'received_at'], 'fbm_ad_wh_connection_received_idx');
            $table->index(['fbm_ad_account_id', 'received_at'], 'fbm_ad_wh_account_received_idx');
            $table->index(['event_type', 'status'], 'fbm_ad_wh_event_status_idx');
        });
    }

    private function ensureReconciliationRuns(): void
    {
        if (Schema::hasTable('fbm_ad_account_reconciliation_runs')) {
            return;
        }

        Schema::create('fbm_ad_account_reconciliation_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('run_uuid')->unique();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('execution_mode', 40);
            $table->string('status', 30);
            $table->string('source', 40)->default('manual');
            $table->unsignedInteger('sync_run_count')->default(0);
            $table->unsignedInteger('failed_sync_count')->default(0);
            $table->unsignedInteger('stale_health_count')->default(0);
            $table->unsignedInteger('alert_count')->default(0);
            $table->json('safe_summary')->nullable();
            $table->string('redacted_message', 500)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'started_at'], 'fbm_ad_recon_status_started_idx');
        });
    }

    private function ensureAlerts(): void
    {
        if (Schema::hasTable('fbm_alerts')) {
            return;
        }

        Schema::create('fbm_alerts', function (Blueprint $table) {
            $table->id();
            $table->uuid('alert_uuid')->unique();
            $table->char('dedupe_key', 64)->unique();
            $table->string('alert_type', 80);
            $table->string('severity', 20);
            $table->string('status', 20)->default('open');
            $table->string('source_type', 80)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('title', 255);
            $table->json('safe_context')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity'], 'fbm_alert_status_severity_idx');
            $table->index(['alert_type', 'last_seen_at'], 'fbm_alert_type_seen_idx');
        });
    }
};
