<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureSyncRuns();
        $this->ensureApiRequestLogs();
        $this->ensureScheduledSyncSettings();
    }

    public function down(): void
    {
        Schema::dropIfExists('fbm_api_request_logs');
        Schema::dropIfExists('fbm_sync_runs');

        // Scheduled-sync settings are intentionally preserved. Dropping these
        // columns during rollback could silently re-enable or reschedule work.
    }

    private function ensureSyncRuns(): void
    {
        if (!Schema::hasTable('fbm_sync_runs')) {
            Schema::create('fbm_sync_runs', function (Blueprint $table) {
                $table->id();
                $table->uuid('run_uuid')->unique();
                $table->unsignedBigInteger('fbm_connection_id');
                $table->string('sync_scope', 80);
                $table->string('trigger_type', 30);
                $table->string('status', 30);
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->timestamp('requested_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('attempt_count')->default(0);
                $table->unsignedInteger('duration_ms')->nullable();
                $table->unsignedInteger('warning_count')->default(0);
                $table->unsignedInteger('error_count')->default(0);
                $table->string('redacted_message', 500)->nullable();
                $table->json('safe_summary')->nullable();
                $table->char('application_context_fingerprint', 64);
                $table->char('lock_fingerprint', 64);
                $table->timestamps();
                $table->index(['fbm_connection_id', 'sync_scope', 'status'], 'fbm_sync_conn_scope_status_idx');
                $table->index(['trigger_type', 'requested_at'], 'fbm_sync_trigger_requested_idx');
                $table->index('completed_at', 'fbm_sync_completed_idx');
            });
        }
    }

    private function ensureApiRequestLogs(): void
    {
        if (!Schema::hasTable('fbm_api_request_logs')) {
            Schema::create('fbm_api_request_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_sync_run_id')->nullable();
                $table->unsignedBigInteger('fbm_connection_id');
                $table->string('operation_key', 80);
                $table->string('graph_api_version', 20)->nullable();
                $table->string('http_method', 10)->default('GET');
                $table->unsignedInteger('page_number')->nullable();
                $table->unsignedSmallInteger('http_status')->nullable();
                $table->unsignedInteger('duration_ms')->nullable();
                $table->string('provider_error_code', 80)->nullable();
                $table->string('provider_error_subcode', 80)->nullable();
                $table->char('request_fingerprint', 64);
                $table->string('redacted_message', 500)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('fbm_sync_run_id', 'fbm_api_log_sync_run_idx');
                $table->index(['fbm_connection_id', 'created_at'], 'fbm_api_log_connection_created_idx');
                $table->index(['operation_key', 'created_at'], 'fbm_api_log_operation_created_idx');
            });
        }
    }

    private function ensureScheduledSyncSettings(): void
    {
        if (!Schema::hasTable('fbm_module_settings')) {
            return;
        }

        $columns = [
            'scheduled_sync_enabled' => fn(Blueprint $table) => $table->boolean('scheduled_sync_enabled')->default(false),
            'scheduled_sync_interval_minutes' => fn(Blueprint $table) => $table->unsignedInteger('scheduled_sync_interval_minutes')->default(60),
            'last_scheduled_sync_dispatched_at' => fn(Blueprint $table) => $table->timestamp('last_scheduled_sync_dispatched_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn('fbm_module_settings', $column)) {
                continue;
            }

            Schema::table('fbm_module_settings', function (Blueprint $table) use ($definition) {
                $definition($table);
            });
        }

        if (!DB::table('fbm_module_settings')->where('id', 1)->exists()) {
            DB::table('fbm_module_settings')->insert([
                'id' => 1,
                'feed_enabled' => true,
                'feed_cache_ttl_minutes' => 360,
                'scheduled_sync_enabled' => false,
                'scheduled_sync_interval_minutes' => 60,
                'last_scheduled_sync_dispatched_at' => null,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
