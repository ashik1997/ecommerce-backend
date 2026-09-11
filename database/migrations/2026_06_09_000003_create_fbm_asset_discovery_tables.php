<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureBusinessAccounts();
        $this->ensureAdAccounts();
        $this->ensurePages();
        $this->ensurePixels();
        $this->ensureDatasets();
        $this->ensureCatalogs();
        $this->ensureInstagramAccounts();
        $this->ensureDiscoveryRuns();
        $this->ensureSelectionAudits();
    }

    public function down(): void
    {
        // Intentionally non-destructive. Discovered asset selections and the
        // append-only operator ledgers require an explicit removal procedure.
    }

    private function ensureBusinessAccounts(): void
    {
        if (!Schema::hasTable('fbm_business_accounts')) {
            Schema::create('fbm_business_accounts', function (Blueprint $table) {
                $this->addAssetIdentityColumns($table, false);
                $table->string('verification_status', 80)->nullable();
                $this->addAssetIndexes($table, 'fbm_business');
            });

            return;
        }

        $this->addMissingColumns('fbm_business_accounts', array_merge(
            $this->assetIdentityColumnDefinitions(false),
            ['verification_status' => fn(Blueprint $table) => $table->string('verification_status', 80)->nullable()]
        ));
    }

    private function ensureAdAccounts(): void
    {
        if (!Schema::hasTable('fbm_ad_accounts')) {
            Schema::create('fbm_ad_accounts', function (Blueprint $table) {
                $this->addAssetIdentityColumns($table);
                $table->string('account_status', 80)->nullable();
                $table->string('currency', 20)->nullable();
                $table->string('timezone_name', 120)->nullable();
                $this->addAssetIndexes($table, 'fbm_ad_account');
            });

            return;
        }

        $this->addMissingColumns('fbm_ad_accounts', array_merge(
            $this->assetIdentityColumnDefinitions(),
            [
                'account_status' => fn(Blueprint $table) => $table->string('account_status', 80)->nullable(),
                'currency' => fn(Blueprint $table) => $table->string('currency', 20)->nullable(),
                'timezone_name' => fn(Blueprint $table) => $table->string('timezone_name', 120)->nullable(),
            ]
        ));
    }

    private function ensurePages(): void
    {
        if (!Schema::hasTable('fbm_pages')) {
            Schema::create('fbm_pages', function (Blueprint $table) {
                $this->addAssetIdentityColumns($table);
                $table->string('category', 160)->nullable();
                $this->addAssetIndexes($table, 'fbm_page');
            });

            return;
        }

        $this->addMissingColumns('fbm_pages', array_merge(
            $this->assetIdentityColumnDefinitions(),
            ['category' => fn(Blueprint $table) => $table->string('category', 160)->nullable()]
        ));
    }

    private function ensurePixels(): void
    {
        if (!Schema::hasTable('fbm_pixels')) {
            Schema::create('fbm_pixels', function (Blueprint $table) {
                $this->addAssetIdentityColumns($table);
                $table->string('data_use_setting', 80)->nullable();
                $this->addAssetIndexes($table, 'fbm_pixel');
            });

            return;
        }

        $this->addMissingColumns('fbm_pixels', array_merge(
            $this->assetIdentityColumnDefinitions(),
            ['data_use_setting' => fn(Blueprint $table) => $table->string('data_use_setting', 80)->nullable()]
        ));
    }

    private function ensureDatasets(): void
    {
        if (!Schema::hasTable('fbm_datasets')) {
            Schema::create('fbm_datasets', function (Blueprint $table) {
                $this->addAssetIdentityColumns($table);
                $table->string('dataset_type', 80)->nullable();
                $this->addAssetIndexes($table, 'fbm_dataset');
            });

            return;
        }

        $this->addMissingColumns('fbm_datasets', array_merge(
            $this->assetIdentityColumnDefinitions(),
            ['dataset_type' => fn(Blueprint $table) => $table->string('dataset_type', 80)->nullable()]
        ));
    }

    private function ensureCatalogs(): void
    {
        if (!Schema::hasTable('fbm_catalogs')) {
            Schema::create('fbm_catalogs', function (Blueprint $table) {
                $this->addAssetIdentityColumns($table);
                $table->string('vertical', 80)->nullable();
                $this->addAssetIndexes($table, 'fbm_catalog');
            });

            return;
        }

        $this->addMissingColumns('fbm_catalogs', array_merge(
            $this->assetIdentityColumnDefinitions(),
            ['vertical' => fn(Blueprint $table) => $table->string('vertical', 80)->nullable()]
        ));
    }

    private function ensureInstagramAccounts(): void
    {
        if (!Schema::hasTable('fbm_instagram_accounts')) {
            Schema::create('fbm_instagram_accounts', function (Blueprint $table) {
                $this->addAssetIdentityColumns($table);
                $table->unsignedBigInteger('fbm_page_id')->nullable();
                $table->string('username', 190)->nullable();
                $this->addAssetIndexes($table, 'fbm_instagram');
                $table->index('fbm_page_id', 'fbm_instagram_page_idx');
            });

            return;
        }

        $this->addMissingColumns('fbm_instagram_accounts', array_merge(
            $this->assetIdentityColumnDefinitions(),
            [
                'fbm_page_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_page_id')->nullable(),
                'username' => fn(Blueprint $table) => $table->string('username', 190)->nullable(),
            ]
        ));
    }

    private function ensureDiscoveryRuns(): void
    {
        if (!Schema::hasTable('fbm_asset_discovery_runs')) {
            Schema::create('fbm_asset_discovery_runs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_connection_id');
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->string('status', 30);
                $table->string('graph_api_version', 20)->nullable();
                $table->json('asset_counts')->nullable();
                $table->json('successful_families')->nullable();
                $table->json('failed_families')->nullable();
                $table->json('warning_details')->nullable();
                $table->string('redacted_message', 500)->nullable();
                $table->unsignedInteger('duration_ms')->nullable();
                $table->char('request_fingerprint', 64);
                $table->char('request_ip_hash', 64)->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->index('fbm_connection_id', 'fbm_discovery_connection_idx');
                $table->index('actor_user_id', 'fbm_discovery_actor_idx');
                $table->index('status', 'fbm_discovery_status_idx');
                $table->index('completed_at', 'fbm_discovery_completed_idx');
            });

            return;
        }

        $this->addMissingColumns('fbm_asset_discovery_runs', [
            'fbm_connection_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_connection_id'),
            'actor_user_id' => fn(Blueprint $table) => $table->unsignedBigInteger('actor_user_id')->nullable(),
            'status' => fn(Blueprint $table) => $table->string('status', 30),
            'graph_api_version' => fn(Blueprint $table) => $table->string('graph_api_version', 20)->nullable(),
            'asset_counts' => fn(Blueprint $table) => $table->json('asset_counts')->nullable(),
            'successful_families' => fn(Blueprint $table) => $table->json('successful_families')->nullable(),
            'failed_families' => fn(Blueprint $table) => $table->json('failed_families')->nullable(),
            'warning_details' => fn(Blueprint $table) => $table->json('warning_details')->nullable(),
            'redacted_message' => fn(Blueprint $table) => $table->string('redacted_message', 500)->nullable(),
            'duration_ms' => fn(Blueprint $table) => $table->unsignedInteger('duration_ms')->nullable(),
            'request_fingerprint' => fn(Blueprint $table) => $table->char('request_fingerprint', 64)->nullable(),
            'request_ip_hash' => fn(Blueprint $table) => $table->char('request_ip_hash', 64)->nullable(),
            'started_at' => fn(Blueprint $table) => $table->timestamp('started_at')->nullable(),
            'completed_at' => fn(Blueprint $table) => $table->timestamp('completed_at')->nullable(),
        ]);
    }

    private function ensureSelectionAudits(): void
    {
        if (!Schema::hasTable('fbm_asset_selection_audits')) {
            Schema::create('fbm_asset_selection_audits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_connection_id');
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->string('asset_type', 50);
                $table->unsignedBigInteger('asset_record_id');
                $table->char('provider_asset_hash', 64);
                $table->boolean('before_selected');
                $table->boolean('after_selected');
                $table->string('change_reason', 500)->nullable();
                $table->char('request_ip_hash', 64)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('fbm_connection_id', 'fbm_selection_connection_idx');
                $table->index(['asset_type', 'asset_record_id'], 'fbm_selection_asset_idx');
                $table->index('actor_user_id', 'fbm_selection_actor_idx');
                $table->index('created_at', 'fbm_selection_created_idx');
            });

            return;
        }

        $this->addMissingColumns('fbm_asset_selection_audits', [
            'fbm_connection_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_connection_id'),
            'actor_user_id' => fn(Blueprint $table) => $table->unsignedBigInteger('actor_user_id')->nullable(),
            'asset_type' => fn(Blueprint $table) => $table->string('asset_type', 50),
            'asset_record_id' => fn(Blueprint $table) => $table->unsignedBigInteger('asset_record_id'),
            'provider_asset_hash' => fn(Blueprint $table) => $table->char('provider_asset_hash', 64)->nullable(),
            'before_selected' => fn(Blueprint $table) => $table->boolean('before_selected')->default(false),
            'after_selected' => fn(Blueprint $table) => $table->boolean('after_selected')->default(false),
            'change_reason' => fn(Blueprint $table) => $table->string('change_reason', 500)->nullable(),
            'request_ip_hash' => fn(Blueprint $table) => $table->char('request_ip_hash', 64)->nullable(),
            'created_at' => fn(Blueprint $table) => $table->timestamp('created_at')->nullable(),
        ]);
    }

    private function addAssetIdentityColumns(Blueprint $table, bool $withBusinessAccount = true): void
    {
        $table->id();
        $table->unsignedBigInteger('fbm_connection_id');
        if ($withBusinessAccount) {
            $table->unsignedBigInteger('fbm_business_account_id')->nullable();
        }
        $table->string('provider_asset_id', 160);
        $table->string('asset_name', 255)->nullable();
        $table->string('asset_relationship', 40)->default('direct');
        $table->string('provider_status', 80)->nullable();
        $table->boolean('is_available')->default(true);
        $table->boolean('is_selected')->default(false);
        $table->timestamp('last_seen_at')->nullable();
        $table->timestamps();
    }

    private function addAssetIndexes(Blueprint $table, string $prefix): void
    {
        $table->unique(['fbm_connection_id', 'provider_asset_id'], $prefix . '_conn_provider_uq');
        $table->index('fbm_connection_id', $prefix . '_connection_idx');
        if ($prefix !== 'fbm_business') {
            $table->index('fbm_business_account_id', $prefix . '_business_idx');
        }
        $table->index(['is_available', 'is_selected'], $prefix . '_availability_idx');
    }

    private function assetIdentityColumnDefinitions(bool $withBusinessAccount = true): array
    {
        $columns = [
            'fbm_connection_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_connection_id'),
            'provider_asset_id' => fn(Blueprint $table) => $table->string('provider_asset_id', 160),
            'asset_name' => fn(Blueprint $table) => $table->string('asset_name', 255)->nullable(),
            'asset_relationship' => fn(Blueprint $table) => $table->string('asset_relationship', 40)->default('direct'),
            'provider_status' => fn(Blueprint $table) => $table->string('provider_status', 80)->nullable(),
            'is_available' => fn(Blueprint $table) => $table->boolean('is_available')->default(true),
            'is_selected' => fn(Blueprint $table) => $table->boolean('is_selected')->default(false),
            'last_seen_at' => fn(Blueprint $table) => $table->timestamp('last_seen_at')->nullable(),
            'created_at' => fn(Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn(Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];

        if ($withBusinessAccount) {
            $columns = array_merge([
                'fbm_business_account_id' => fn(Blueprint $table) => $table->unsignedBigInteger('fbm_business_account_id')->nullable(),
            ], $columns);
        }

        return $columns;
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
