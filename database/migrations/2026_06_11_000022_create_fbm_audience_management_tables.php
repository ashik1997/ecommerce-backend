<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fbm_audiences')) {
            Schema::create('fbm_audiences', function (Blueprint $table) {
                $table->id();
                $table->uuid('audience_uuid')->unique();
                $table->unsignedBigInteger('fbm_connection_id')->nullable();
                $table->unsignedBigInteger('fbm_ad_account_id')->nullable();
                $table->string('provider_audience_id', 190)->nullable();
                $table->string('audience_type', 40)->default('saved');
                $table->string('audience_name', 255);
                $table->string('subtype', 80)->nullable();
                $table->string('description', 500)->nullable();
                $table->json('source_summary')->nullable();
                $table->unsignedInteger('approximate_count')->nullable();
                $table->string('status', 40)->default('draft');
                $table->boolean('is_available')->default(true);
                $table->boolean('is_selected')->default(false);
                $table->string('planned_use', 120)->nullable();
                $table->string('consent_basis', 120)->nullable();
                $table->string('consent_note', 500)->nullable();
                $table->unsignedSmallInteger('retention_days')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->unsignedBigInteger('selected_by')->nullable();
                $table->timestamp('selected_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['fbm_ad_account_id', 'provider_audience_id'], 'fbm_audience_provider_uq');
                $table->index(['audience_type', 'status'], 'fbm_audience_type_status_idx');
                $table->index(['is_available', 'is_selected'], 'fbm_audience_selection_idx');
            });
        }

        $this->addMissingColumns('fbm_audiences', [
            'audience_uuid' => fn(Blueprint $table) => $table->uuid('audience_uuid')->nullable()->unique(),
            'fbm_connection_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_connection_id')->nullable(),
            'fbm_ad_account_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_ad_account_id')->nullable(),
            'provider_audience_id' => fn(Blueprint $table) => $table->string('provider_audience_id', 190)->nullable(),
            'audience_type' => fn(Blueprint $table) => $table->string('audience_type', 40)->default('saved'),
            'audience_name' => fn(Blueprint $table) => $table->string('audience_name', 255)->nullable(),
            'subtype' => fn(Blueprint $table) => $table->string('subtype', 80)->nullable(),
            'description' => fn(Blueprint $table) => $table->string('description', 500)->nullable(),
            'source_summary' => fn(Blueprint $table) => $table->json('source_summary')->nullable(),
            'approximate_count' => fn(Blueprint $table) => $table->unsignedInteger('approximate_count')->nullable(),
            'status' => fn(Blueprint $table) => $table->string('status', 40)->default('draft'),
            'is_available' => fn(Blueprint $table) => $table->boolean('is_available')->default(true),
            'is_selected' => fn(Blueprint $table) => $table->boolean('is_selected')->default(false),
            'planned_use' => fn(Blueprint $table) => $table->string('planned_use', 120)->nullable(),
            'consent_basis' => fn(Blueprint $table) => $table->string('consent_basis', 120)->nullable(),
            'consent_note' => fn(Blueprint $table) => $table->string('consent_note', 500)->nullable(),
            'retention_days' => fn(Blueprint $table) => $table->unsignedSmallInteger('retention_days')->nullable(),
            'last_synced_at' => fn(Blueprint $table) => $table->timestamp('last_synced_at')->nullable(),
            'selected_by' => fn(Blueprint $table) => $table->unsignedBigInteger('selected_by')->nullable(),
            'selected_at' => fn(Blueprint $table) => $table->timestamp('selected_at')->nullable(),
            'created_by' => fn(Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable(),
            'updated_by' => fn(Blueprint $table) => $table->unsignedBigInteger('updated_by')->nullable(),
            'created_at' => fn(Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn(Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ]);

        if (Schema::hasTable('fbm_product_sets')) {
            $this->addMissingColumns('fbm_product_sets', [
                'is_selected' => fn(Blueprint $table) => $table->boolean('is_selected')->default(false),
                'selection_status' => fn(Blueprint $table) => $table->string('selection_status', 40)->default('available'),
                'planned_use' => fn(Blueprint $table) => $table->string('planned_use', 120)->nullable(),
                'consent_note' => fn(Blueprint $table) => $table->string('consent_note', 500)->nullable(),
                'selected_by' => fn(Blueprint $table) => $table->unsignedBigInteger('selected_by')->nullable(),
                'selected_at' => fn(Blueprint $table) => $table->timestamp('selected_at')->nullable(),
            ]);
        }

        if (!Schema::hasTable('fbm_audience_sync_runs')) {
            Schema::create('fbm_audience_sync_runs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_connection_id');
                $table->unsignedBigInteger('fbm_ad_account_id')->nullable();
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->string('execution_mode', 40)->default('manual');
                $table->string('status', 30);
                $table->string('graph_api_version', 20)->nullable();
                $table->unsignedInteger('saved_audience_count')->default(0);
                $table->unsignedInteger('custom_audience_count')->default(0);
                $table->json('warnings')->nullable();
                $table->string('redacted_message', 500)->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['fbm_connection_id', 'created_at'], 'fbm_audience_sync_connection_idx');
            });
        }

        if (!Schema::hasTable('fbm_audience_selection_audits')) {
            Schema::create('fbm_audience_selection_audits', function (Blueprint $table) {
                $table->id();
                $table->string('object_type', 40);
                $table->unsignedBigInteger('object_id');
                $table->string('action', 60);
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->string('reason', 500)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['object_type', 'object_id'], 'fbm_audience_audit_object_idx');
            });
        }
    }

    public function down(): void
    {
        // Audience plans, product-set selection flags and audit rows are retained intentionally.
    }

    private function addMissingColumns(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column => $callback) {
            if (!Schema::hasColumn($table, $column)) {
                Schema::table($table, fn(Blueprint $table) => $callback($table));
            }
        }
    }
};
