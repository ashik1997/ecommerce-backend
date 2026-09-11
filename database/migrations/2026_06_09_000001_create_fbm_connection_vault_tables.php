<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fbm_connections')) {
            Schema::create('fbm_connections', function (Blueprint $table) {
                $table->id();
                $table->string('connection_name', 120);
                $table->string('app_id', 120)->nullable();
                $table->string('credential_mode', 40)->default('system_user');
                $table->string('graph_api_version', 20)->nullable();
                $table->text('app_secret_ciphertext')->nullable();
                $table->longText('access_token_ciphertext')->nullable();
                $table->longText('capi_access_token_ciphertext')->nullable();
                $table->text('webhook_verify_token_ciphertext')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->unsignedInteger('secret_version')->default(0);
                $table->timestamp('credential_updated_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('disabled_by')->nullable();
                $table->timestamp('disabled_at')->nullable();
                $table->timestamps();

                $table->unique('connection_name', 'fbm_connections_name_unique');
                $table->index('is_active', 'fbm_connections_active_idx');
                $table->index('credential_mode', 'fbm_connections_mode_idx');
                $table->index('credential_updated_at', 'fbm_connections_credential_updated_idx');
            });
        } else {
            $this->addMissingConnectionColumns();
        }

        if (!Schema::hasTable('fbm_connection_secret_audits')) {
            Schema::create('fbm_connection_secret_audits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_connection_id');
                $table->string('action', 40);
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->json('changed_fields')->nullable();
                $table->json('configured_secret_fields')->nullable();
                $table->json('secret_fingerprints')->nullable();
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->char('request_ip_hash', 64)->nullable();
                $table->string('change_reason', 500)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('fbm_connection_id', 'fbm_secret_audits_connection_idx');
                $table->index('action', 'fbm_secret_audits_action_idx');
                $table->index('actor_user_id', 'fbm_secret_audits_actor_idx');
                $table->index('created_at', 'fbm_secret_audits_created_idx');
            });
        } else {
            $this->addMissingAuditColumns();
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: credential audit history and encrypted
        // provider configuration require an explicit operator-approved removal.
    }

    private function addMissingConnectionColumns(): void
    {
        $this->addMissingColumns('fbm_connections', [
            'connection_name' => fn(Blueprint $table) => $table->string('connection_name', 120),
            'app_id' => fn(Blueprint $table) => $table->string('app_id', 120)->nullable(),
            'credential_mode' => fn(Blueprint $table) => $table->string('credential_mode', 40)->default('system_user'),
            'graph_api_version' => fn(Blueprint $table) => $table->string('graph_api_version', 20)->nullable(),
            'app_secret_ciphertext' => fn(Blueprint $table) => $table->text('app_secret_ciphertext')->nullable(),
            'access_token_ciphertext' => fn(Blueprint $table) => $table->longText('access_token_ciphertext')->nullable(),
            'capi_access_token_ciphertext' => fn(Blueprint $table) => $table->longText('capi_access_token_ciphertext')->nullable(),
            'webhook_verify_token_ciphertext' => fn(Blueprint $table) => $table->text('webhook_verify_token_ciphertext')->nullable(),
            'is_active' => fn(Blueprint $table) => $table->boolean('is_active')->default(true),
            'notes' => fn(Blueprint $table) => $table->text('notes')->nullable(),
            'secret_version' => fn(Blueprint $table) => $table->unsignedInteger('secret_version')->default(0),
            'credential_updated_at' => fn(Blueprint $table) => $table->timestamp('credential_updated_at')->nullable(),
            'created_by' => fn(Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable(),
            'updated_by' => fn(Blueprint $table) => $table->unsignedBigInteger('updated_by')->nullable(),
            'disabled_by' => fn(Blueprint $table) => $table->unsignedBigInteger('disabled_by')->nullable(),
            'disabled_at' => fn(Blueprint $table) => $table->timestamp('disabled_at')->nullable(),
            'created_at' => fn(Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn(Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ]);
    }

    private function addMissingAuditColumns(): void
    {
        $this->addMissingColumns('fbm_connection_secret_audits', [
            'fbm_connection_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_connection_id'),
            'action' => fn(Blueprint $table) => $table->string('action', 40),
            'actor_user_id' => fn(Blueprint $table) => $table->unsignedBigInteger('actor_user_id')->nullable(),
            'changed_fields' => fn(Blueprint $table) => $table->json('changed_fields')->nullable(),
            'configured_secret_fields' => fn(Blueprint $table) => $table->json('configured_secret_fields')->nullable(),
            'secret_fingerprints' => fn(Blueprint $table) => $table->json('secret_fingerprints')->nullable(),
            'before_state' => fn(Blueprint $table) => $table->json('before_state')->nullable(),
            'after_state' => fn(Blueprint $table) => $table->json('after_state')->nullable(),
            'request_ip_hash' => fn(Blueprint $table) => $table->char('request_ip_hash', 64)->nullable(),
            'change_reason' => fn(Blueprint $table) => $table->string('change_reason', 500)->nullable(),
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
