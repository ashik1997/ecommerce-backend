<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fbm_connection_health_checks')) {
            Schema::create('fbm_connection_health_checks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_connection_id');
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->string('status', 20);
                $table->string('graph_api_version', 20)->nullable();
                $table->boolean('token_is_valid')->nullable();
                $table->string('token_type', 100)->nullable();
                $table->string('provider_app_id', 120)->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('data_access_expires_at')->nullable();
                $table->json('scopes')->nullable();
                $table->json('missing_required_scopes')->nullable();
                $table->unsignedSmallInteger('http_status')->nullable();
                $table->string('provider_error_code', 80)->nullable();
                $table->string('provider_error_subcode', 80)->nullable();
                $table->string('redacted_message', 500)->nullable();
                $table->unsignedInteger('duration_ms')->nullable();
                $table->char('request_fingerprint', 64);
                $table->char('request_ip_hash', 64)->nullable();
                $table->timestamp('checked_at')->useCurrent();

                $table->index('fbm_connection_id', 'fbm_health_checks_connection_idx');
                $table->index('actor_user_id', 'fbm_health_checks_actor_idx');
                $table->index('status', 'fbm_health_checks_status_idx');
                $table->index('checked_at', 'fbm_health_checks_checked_idx');
            });
        } else {
            $this->addMissingColumns();
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: the append-only connection health
        // ledger requires an explicit operator-approved removal procedure.
    }

    private function addMissingColumns(): void
    {
        $columns = [
            'fbm_connection_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_connection_id'),
            'actor_user_id' => fn(Blueprint $table) => $table->unsignedBigInteger('actor_user_id')->nullable(),
            'status' => fn(Blueprint $table) => $table->string('status', 20),
            'graph_api_version' => fn(Blueprint $table) => $table->string('graph_api_version', 20)->nullable(),
            'token_is_valid' => fn(Blueprint $table) => $table->boolean('token_is_valid')->nullable(),
            'token_type' => fn(Blueprint $table) => $table->string('token_type', 100)->nullable(),
            'provider_app_id' => fn(Blueprint $table) => $table->string('provider_app_id', 120)->nullable(),
            'issued_at' => fn(Blueprint $table) => $table->timestamp('issued_at')->nullable(),
            'expires_at' => fn(Blueprint $table) => $table->timestamp('expires_at')->nullable(),
            'data_access_expires_at' => fn(Blueprint $table) => $table->timestamp('data_access_expires_at')->nullable(),
            'scopes' => fn(Blueprint $table) => $table->json('scopes')->nullable(),
            'missing_required_scopes' => fn(Blueprint $table) => $table->json('missing_required_scopes')->nullable(),
            'http_status' => fn(Blueprint $table) => $table->unsignedSmallInteger('http_status')->nullable(),
            'provider_error_code' => fn(Blueprint $table) => $table->string('provider_error_code', 80)->nullable(),
            'provider_error_subcode' => fn(Blueprint $table) => $table->string('provider_error_subcode', 80)->nullable(),
            'redacted_message' => fn(Blueprint $table) => $table->string('redacted_message', 500)->nullable(),
            'duration_ms' => fn(Blueprint $table) => $table->unsignedInteger('duration_ms')->nullable(),
            'request_fingerprint' => fn(Blueprint $table) => $table->char('request_fingerprint', 64)->nullable(),
            'request_ip_hash' => fn(Blueprint $table) => $table->char('request_ip_hash', 64)->nullable(),
            'checked_at' => fn(Blueprint $table) => $table->timestamp('checked_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn('fbm_connection_health_checks', $column)) {
                continue;
            }

            Schema::table('fbm_connection_health_checks', function (Blueprint $table) use ($definition) {
                $definition($table);
            });
        }
    }
};
