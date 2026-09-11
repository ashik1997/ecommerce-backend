<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureCatalogProductMappings();
        $this->ensureProductSets();
        $this->ensureCatalogSyncRuns();
    }

    public function down(): void
    {
        // Intentionally non-destructive. Catalog mappings, operator overrides
        // and append-only sync ledgers require an explicit removal procedure.
    }

    private function ensureCatalogProductMappings(): void
    {
        if (!Schema::hasTable('fbm_catalog_product_mappings')) {
            Schema::create('fbm_catalog_product_mappings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_catalog_id');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('provider_product_item_id', 190);
                $table->string('retailer_id', 190)->nullable();
                $table->string('retailer_product_group_id', 190)->nullable();
                $table->string('item_name', 255)->nullable();
                $table->string('provider_availability', 80)->nullable();
                $table->decimal('provider_price_amount', 18, 4)->nullable();
                $table->string('provider_currency', 20)->nullable();
                $table->string('mapping_status', 30)->default('unmatched');
                $table->string('mapping_source', 30)->nullable();
                $table->json('diagnostic_flags')->nullable();
                $table->boolean('is_available')->default(true);
                $table->timestamp('last_seen_at')->nullable();
                $table->unsignedBigInteger('mapped_by')->nullable();
                $table->timestamp('mapped_at')->nullable();
                $table->string('mapping_note', 500)->nullable();
                $table->timestamps();
                $table->unique(['fbm_catalog_id', 'provider_product_item_id'], 'fbm_catalog_item_provider_uq');
                $table->index(['fbm_catalog_id', 'mapping_status'], 'fbm_catalog_item_status_idx');
                $table->index(['product_id', 'is_available'], 'fbm_catalog_item_product_idx');
                $table->index(['retailer_id', 'is_available'], 'fbm_catalog_item_retailer_idx');
            });

            return;
        }

        $this->addMissingColumns('fbm_catalog_product_mappings', [
            'fbm_catalog_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_catalog_id'),
            'product_id' => fn(Blueprint $table) => $table->unsignedBigInteger('product_id')->nullable(),
            'provider_product_item_id' => fn(Blueprint $table) => $table->string('provider_product_item_id', 190),
            'retailer_id' => fn(Blueprint $table) => $table->string('retailer_id', 190)->nullable(),
            'retailer_product_group_id' => fn(Blueprint $table) => $table->string('retailer_product_group_id', 190)->nullable(),
            'item_name' => fn(Blueprint $table) => $table->string('item_name', 255)->nullable(),
            'provider_availability' => fn(Blueprint $table) => $table->string('provider_availability', 80)->nullable(),
            'provider_price_amount' => fn(Blueprint $table) => $table->decimal('provider_price_amount', 18, 4)->nullable(),
            'provider_currency' => fn(Blueprint $table) => $table->string('provider_currency', 20)->nullable(),
            'mapping_status' => fn(Blueprint $table) => $table->string('mapping_status', 30)->default('unmatched'),
            'mapping_source' => fn(Blueprint $table) => $table->string('mapping_source', 30)->nullable(),
            'diagnostic_flags' => fn(Blueprint $table) => $table->json('diagnostic_flags')->nullable(),
            'is_available' => fn(Blueprint $table) => $table->boolean('is_available')->default(true),
            'last_seen_at' => fn(Blueprint $table) => $table->timestamp('last_seen_at')->nullable(),
            'mapped_by' => fn(Blueprint $table) => $table->unsignedBigInteger('mapped_by')->nullable(),
            'mapped_at' => fn(Blueprint $table) => $table->timestamp('mapped_at')->nullable(),
            'mapping_note' => fn(Blueprint $table) => $table->string('mapping_note', 500)->nullable(),
            'created_at' => fn(Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn(Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ]);
    }

    private function ensureProductSets(): void
    {
        if (!Schema::hasTable('fbm_product_sets')) {
            Schema::create('fbm_product_sets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_catalog_id');
                $table->string('provider_product_set_id', 190);
                $table->string('set_name', 255)->nullable();
                $table->json('filter_summary')->nullable();
                $table->unsignedInteger('item_count')->nullable();
                $table->boolean('is_available')->default(true);
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
                $table->unique(['fbm_catalog_id', 'provider_product_set_id'], 'fbm_product_set_provider_uq');
                $table->index(['fbm_catalog_id', 'is_available'], 'fbm_product_set_catalog_available_idx');
            });

            return;
        }

        $this->addMissingColumns('fbm_product_sets', [
            'fbm_catalog_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_catalog_id'),
            'provider_product_set_id' => fn(Blueprint $table) => $table->string('provider_product_set_id', 190),
            'set_name' => fn(Blueprint $table) => $table->string('set_name', 255)->nullable(),
            'filter_summary' => fn(Blueprint $table) => $table->json('filter_summary')->nullable(),
            'item_count' => fn(Blueprint $table) => $table->unsignedInteger('item_count')->nullable(),
            'is_available' => fn(Blueprint $table) => $table->boolean('is_available')->default(true),
            'last_seen_at' => fn(Blueprint $table) => $table->timestamp('last_seen_at')->nullable(),
            'created_at' => fn(Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn(Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ]);
    }

    private function ensureCatalogSyncRuns(): void
    {
        if (!Schema::hasTable('fbm_catalog_sync_runs')) {
            Schema::create('fbm_catalog_sync_runs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_connection_id');
                $table->unsignedBigInteger('fbm_catalog_id')->nullable();
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->string('execution_mode', 40);
                $table->string('status', 30);
                $table->string('graph_api_version', 20)->nullable();
                $table->unsignedInteger('catalog_count')->default(0);
                $table->unsignedInteger('product_item_count')->default(0);
                $table->unsignedInteger('product_set_count')->default(0);
                $table->unsignedInteger('automatic_mapping_count')->default(0);
                $table->unsignedInteger('manual_mapping_count')->default(0);
                $table->unsignedInteger('unmatched_count')->default(0);
                $table->unsignedInteger('ambiguous_count')->default(0);
                $table->json('warning_details')->nullable();
                $table->string('redacted_message', 500)->nullable();
                $table->unsignedInteger('duration_ms')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['fbm_connection_id', 'created_at'], 'fbm_catalog_sync_connection_created_idx');
                $table->index(['fbm_catalog_id', 'created_at'], 'fbm_catalog_sync_catalog_created_idx');
                $table->index(['status', 'created_at'], 'fbm_catalog_sync_status_created_idx');
            });

            return;
        }

        $this->addMissingColumns('fbm_catalog_sync_runs', [
            'fbm_connection_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_connection_id'),
            'fbm_catalog_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_catalog_id')->nullable(),
            'actor_user_id' => fn(Blueprint $table) => $table->unsignedBigInteger('actor_user_id')->nullable(),
            'execution_mode' => fn(Blueprint $table) => $table->string('execution_mode', 40),
            'status' => fn(Blueprint $table) => $table->string('status', 30),
            'graph_api_version' => fn(Blueprint $table) => $table->string('graph_api_version', 20)->nullable(),
            'catalog_count' => fn(Blueprint $table) => $table->unsignedInteger('catalog_count')->default(0),
            'product_item_count' => fn(Blueprint $table) => $table->unsignedInteger('product_item_count')->default(0),
            'product_set_count' => fn(Blueprint $table) => $table->unsignedInteger('product_set_count')->default(0),
            'automatic_mapping_count' => fn(Blueprint $table) => $table->unsignedInteger('automatic_mapping_count')->default(0),
            'manual_mapping_count' => fn(Blueprint $table) => $table->unsignedInteger('manual_mapping_count')->default(0),
            'unmatched_count' => fn(Blueprint $table) => $table->unsignedInteger('unmatched_count')->default(0),
            'ambiguous_count' => fn(Blueprint $table) => $table->unsignedInteger('ambiguous_count')->default(0),
            'warning_details' => fn(Blueprint $table) => $table->json('warning_details')->nullable(),
            'redacted_message' => fn(Blueprint $table) => $table->string('redacted_message', 500)->nullable(),
            'duration_ms' => fn(Blueprint $table) => $table->unsignedInteger('duration_ms')->nullable(),
            'started_at' => fn(Blueprint $table) => $table->timestamp('started_at')->nullable(),
            'completed_at' => fn(Blueprint $table) => $table->timestamp('completed_at')->nullable(),
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
