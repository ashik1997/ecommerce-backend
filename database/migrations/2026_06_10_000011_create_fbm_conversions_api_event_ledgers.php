<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureConnectionCapiTestCode();
        $this->ensureServerCapiSetting();
        $this->ensureConversionEvents();
        $this->ensureConversionEventAttempts();
    }

    public function down(): void
    {
        // Conversion delivery ledgers, encrypted provider settings and operator
        // choices are intentionally preserved. Removal requires an explicit,
        // separately reviewed data-retention decision.
    }

    private function ensureConnectionCapiTestCode(): void
    {
        if (!Schema::hasTable('fbm_connections')
            || Schema::hasColumn('fbm_connections', 'capi_test_event_code_ciphertext')) {
            return;
        }

        Schema::table('fbm_connections', function (Blueprint $table) {
            $table->text('capi_test_event_code_ciphertext')->nullable();
        });
    }

    private function ensureServerCapiSetting(): void
    {
        if (!Schema::hasTable('fbm_module_settings')
            || Schema::hasColumn('fbm_module_settings', 'server_capi_mode')) {
            return;
        }

        Schema::table('fbm_module_settings', function (Blueprint $table) {
            $table->string('server_capi_mode', 20)->default('disabled');
        });
    }

    private function ensureConversionEvents(): void
    {
        if (!Schema::hasTable('fbm_conversion_events')) {
            Schema::create('fbm_conversion_events', function (Blueprint $table) {
                $table->id();
                $table->uuid('event_uuid')->unique();
                $table->unsignedBigInteger('fbm_connection_id');
                $table->unsignedBigInteger('fbm_visitor_attribution_session_id')->nullable();
                $table->string('event_name', 80);
                $table->longText('event_id_ciphertext');
                $table->char('event_id_hash', 64);
                $table->text('destination_id_ciphertext');
                $table->char('destination_id_hash', 64);
                $table->string('action_source', 40)->default('website');
                $table->unsignedBigInteger('event_time');
                $table->longText('event_source_url_ciphertext');
                $table->longText('user_data_ciphertext');
                $table->longText('custom_data_ciphertext');
                $table->string('delivery_mode_snapshot', 20);
                $table->string('status', 40)->default('pending');
                $table->unsignedInteger('attempt_count')->default(0);
                $table->timestamp('next_attempt_at')->nullable();
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->unique(
                    ['destination_id_hash', 'event_name', 'event_id_hash'],
                    'fbm_capi_events_dedupe_unique'
                );
                $table->index(['status', 'next_attempt_at'], 'fbm_capi_events_retry_idx');
                $table->index('fbm_connection_id', 'fbm_capi_events_connection_idx');
                $table->index('created_at', 'fbm_capi_events_created_idx');
            });

            return;
        }

        $this->addMissingColumns('fbm_conversion_events', [
            'event_uuid' => fn(Blueprint $table) => $table->uuid('event_uuid')->nullable(),
            'fbm_connection_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_connection_id')->nullable(),
            'fbm_visitor_attribution_session_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_visitor_attribution_session_id')->nullable(),
            'event_name' => fn(Blueprint $table) => $table->string('event_name', 80)->nullable(),
            'event_id_ciphertext' => fn(Blueprint $table) => $table->longText('event_id_ciphertext')->nullable(),
            'event_id_hash' => fn(Blueprint $table) => $table->char('event_id_hash', 64)->nullable(),
            'destination_id_ciphertext' => fn(Blueprint $table) => $table->text('destination_id_ciphertext')->nullable(),
            'destination_id_hash' => fn(Blueprint $table) => $table->char('destination_id_hash', 64)->nullable(),
            'action_source' => fn(Blueprint $table) => $table->string('action_source', 40)->default('website'),
            'event_time' => fn(Blueprint $table) => $table->unsignedBigInteger('event_time')->nullable(),
            'event_source_url_ciphertext' => fn(Blueprint $table) => $table->longText('event_source_url_ciphertext')->nullable(),
            'user_data_ciphertext' => fn(Blueprint $table) => $table->longText('user_data_ciphertext')->nullable(),
            'custom_data_ciphertext' => fn(Blueprint $table) => $table->longText('custom_data_ciphertext')->nullable(),
            'delivery_mode_snapshot' => fn(Blueprint $table) => $table->string('delivery_mode_snapshot', 20)->default('disabled'),
            'status' => fn(Blueprint $table) => $table->string('status', 40)->default('pending'),
            'attempt_count' => fn(Blueprint $table) => $table->unsignedInteger('attempt_count')->default(0),
            'next_attempt_at' => fn(Blueprint $table) => $table->timestamp('next_attempt_at')->nullable(),
            'last_attempt_at' => fn(Blueprint $table) => $table->timestamp('last_attempt_at')->nullable(),
            'delivered_at' => fn(Blueprint $table) => $table->timestamp('delivered_at')->nullable(),
            'created_by' => fn(Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable(),
            'created_at' => fn(Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn(Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ]);
    }

    private function ensureConversionEventAttempts(): void
    {
        if (!Schema::hasTable('fbm_conversion_event_attempts')) {
            Schema::create('fbm_conversion_event_attempts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_conversion_event_id');
                $table->unsignedInteger('attempt_number');
                $table->string('origin', 30);
                $table->string('delivery_mode', 20);
                $table->string('status', 40);
                $table->unsignedSmallInteger('http_status')->nullable();
                $table->string('provider_error_code', 80)->nullable();
                $table->string('provider_error_subcode', 80)->nullable();
                $table->string('redacted_message', 500)->nullable();
                $table->char('request_fingerprint', 64)->nullable();
                $table->unsignedInteger('duration_ms')->nullable();
                $table->timestamp('attempted_at');
                $table->timestamp('created_at')->useCurrent();

                $table->index('fbm_conversion_event_id', 'fbm_capi_attempts_event_idx');
                $table->index(['status', 'attempted_at'], 'fbm_capi_attempts_status_idx');
            });

            return;
        }

        $this->addMissingColumns('fbm_conversion_event_attempts', [
            'fbm_conversion_event_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_conversion_event_id')->nullable(),
            'attempt_number' => fn(Blueprint $table) => $table->unsignedInteger('attempt_number')->default(1),
            'origin' => fn(Blueprint $table) => $table->string('origin', 30)->default('manual'),
            'delivery_mode' => fn(Blueprint $table) => $table->string('delivery_mode', 20)->default('disabled'),
            'status' => fn(Blueprint $table) => $table->string('status', 40)->default('rejected'),
            'http_status' => fn(Blueprint $table) => $table->unsignedSmallInteger('http_status')->nullable(),
            'provider_error_code' => fn(Blueprint $table) => $table->string('provider_error_code', 80)->nullable(),
            'provider_error_subcode' => fn(Blueprint $table) => $table->string('provider_error_subcode', 80)->nullable(),
            'redacted_message' => fn(Blueprint $table) => $table->string('redacted_message', 500)->nullable(),
            'request_fingerprint' => fn(Blueprint $table) => $table->char('request_fingerprint', 64)->nullable(),
            'duration_ms' => fn(Blueprint $table) => $table->unsignedInteger('duration_ms')->nullable(),
            'attempted_at' => fn(Blueprint $table) => $table->timestamp('attempted_at')->nullable(),
            'created_at' => fn(Blueprint $table) => $table->timestamp('created_at')->nullable(),
        ]);
    }

    private function addMissingColumns(string $tableName, array $columns): void
    {
        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn($tableName, $column)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($definition) {
                $definition($table);
            });
        }
    }
};
