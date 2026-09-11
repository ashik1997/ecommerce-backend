<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ATTEMPTS_TABLE = 'crm_campaign_dispatch_attempts';
    private const RECIPIENT_ATTEMPTS_TABLE = 'crm_campaign_dispatch_recipient_attempts';

    public function up(): void
    {
        $this->ensureAttemptsTable();
        $this->ensureRecipientAttemptsTable();
    }

    public function down(): void
    {
        // Intentionally non-destructive: manual dispatch-attempt identity ledgers and audit metadata are retained.
    }

    private function ensureAttemptsTable(): void
    {
        if (!Schema::hasTable(self::ATTEMPTS_TABLE)) {
            Schema::create(self::ATTEMPTS_TABLE, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('crm_campaign_draft_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_preparation_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_run_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_execution_batch_id');
                $table->unsignedInteger('attempt_number');
                $table->string('status', 20)->default('prepared');
                $table->unsignedTinyInteger('active_slot')->nullable();
                $table->string('channel', 30);
                $table->string('provider_key', 50);
                $table->unsignedSmallInteger('provider_request_snapshot_version');
                $table->json('provider_request_snapshot_json')->nullable();
                $table->unsignedBigInteger('recipient_count');
                $table->string('attempt_idempotency_key', 64);
                $table->string('attempt_integrity_signature', 64)->nullable();
                $table->unsignedSmallInteger('attempt_integrity_signature_version')->nullable();
                $table->unsignedBigInteger('provider_request_count')->default(0);
                $table->unsignedBigInteger('provider_success_count')->default(0);
                $table->unsignedBigInteger('provider_failure_count')->default(0);
                $table->unsignedBigInteger('provider_unknown_count')->default(0);
                $table->unsignedBigInteger('prepared_by')->nullable();
                $table->timestamp('prepared_at')->nullable();
                $table->unsignedBigInteger('started_by')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->text('failure_summary')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->json('metadata_json')->nullable();
                $table->timestamps();

                $table->index('product_website_id', 'crm_campaign_dispatch_attempts_website_idx');
                $table->index('crm_campaign_draft_id', 'crm_campaign_dispatch_attempts_draft_idx');
                $table->index('crm_campaign_dispatch_execution_batch_id', 'crm_campaign_dispatch_attempts_batch_idx');
                $table->index('status', 'crm_campaign_dispatch_attempts_status_idx');
                $table->index('prepared_by', 'crm_campaign_dispatch_attempts_prepared_by_idx');
                $table->unique(['crm_campaign_dispatch_execution_batch_id', 'active_slot'], 'crm_campaign_dispatch_attempts_active_unique');
                $table->unique(['crm_campaign_dispatch_execution_batch_id', 'attempt_number'], 'crm_campaign_dispatch_attempts_number_unique');
                $table->unique('attempt_idempotency_key', 'crm_campaign_dispatch_attempts_idem_unique');
            });

            return;
        }

        $this->addMissingColumns(self::ATTEMPTS_TABLE, [
            'product_website_id' => fn (Blueprint $table) => $table->unsignedBigInteger('product_website_id')->nullable(),
            'crm_campaign_draft_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_draft_id')->nullable(),
            'crm_campaign_dispatch_preparation_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_preparation_id')->nullable(),
            'crm_campaign_dispatch_run_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_run_id')->nullable(),
            'crm_campaign_dispatch_execution_batch_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_execution_batch_id')->nullable(),
            'attempt_number' => fn (Blueprint $table) => $table->unsignedInteger('attempt_number')->nullable(),
            'status' => fn (Blueprint $table) => $table->string('status', 20)->default('prepared'),
            'active_slot' => fn (Blueprint $table) => $table->unsignedTinyInteger('active_slot')->nullable(),
            'channel' => fn (Blueprint $table) => $table->string('channel', 30)->nullable(),
            'provider_key' => fn (Blueprint $table) => $table->string('provider_key', 50)->nullable(),
            'provider_request_snapshot_version' => fn (Blueprint $table) => $table->unsignedSmallInteger('provider_request_snapshot_version')->nullable(),
            'provider_request_snapshot_json' => fn (Blueprint $table) => $table->json('provider_request_snapshot_json')->nullable(),
            'recipient_count' => fn (Blueprint $table) => $table->unsignedBigInteger('recipient_count')->nullable(),
            'attempt_idempotency_key' => fn (Blueprint $table) => $table->string('attempt_idempotency_key', 64)->nullable(),
            'attempt_integrity_signature' => fn (Blueprint $table) => $table->string('attempt_integrity_signature', 64)->nullable(),
            'attempt_integrity_signature_version' => fn (Blueprint $table) => $table->unsignedSmallInteger('attempt_integrity_signature_version')->nullable(),
            'provider_request_count' => fn (Blueprint $table) => $table->unsignedBigInteger('provider_request_count')->default(0),
            'provider_success_count' => fn (Blueprint $table) => $table->unsignedBigInteger('provider_success_count')->default(0),
            'provider_failure_count' => fn (Blueprint $table) => $table->unsignedBigInteger('provider_failure_count')->default(0),
            'provider_unknown_count' => fn (Blueprint $table) => $table->unsignedBigInteger('provider_unknown_count')->default(0),
            'prepared_by' => fn (Blueprint $table) => $table->unsignedBigInteger('prepared_by')->nullable(),
            'prepared_at' => fn (Blueprint $table) => $table->timestamp('prepared_at')->nullable(),
            'started_by' => fn (Blueprint $table) => $table->unsignedBigInteger('started_by')->nullable(),
            'started_at' => fn (Blueprint $table) => $table->timestamp('started_at')->nullable(),
            'completed_at' => fn (Blueprint $table) => $table->timestamp('completed_at')->nullable(),
            'failed_at' => fn (Blueprint $table) => $table->timestamp('failed_at')->nullable(),
            'failure_summary' => fn (Blueprint $table) => $table->text('failure_summary')->nullable(),
            'cancelled_by' => fn (Blueprint $table) => $table->unsignedBigInteger('cancelled_by')->nullable(),
            'cancelled_at' => fn (Blueprint $table) => $table->timestamp('cancelled_at')->nullable(),
            'cancellation_reason' => fn (Blueprint $table) => $table->text('cancellation_reason')->nullable(),
            'metadata_json' => fn (Blueprint $table) => $table->json('metadata_json')->nullable(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ]);

        $this->addIndexIfPossible(self::ATTEMPTS_TABLE, ['product_website_id'], 'crm_campaign_dispatch_attempts_website_idx');
        $this->addIndexIfPossible(self::ATTEMPTS_TABLE, ['crm_campaign_draft_id'], 'crm_campaign_dispatch_attempts_draft_idx');
        $this->addIndexIfPossible(self::ATTEMPTS_TABLE, ['crm_campaign_dispatch_execution_batch_id'], 'crm_campaign_dispatch_attempts_batch_idx');
        $this->addIndexIfPossible(self::ATTEMPTS_TABLE, ['status'], 'crm_campaign_dispatch_attempts_status_idx');
        $this->addIndexIfPossible(self::ATTEMPTS_TABLE, ['prepared_by'], 'crm_campaign_dispatch_attempts_prepared_by_idx');
        $this->addUniqueIfPossible(self::ATTEMPTS_TABLE, ['crm_campaign_dispatch_execution_batch_id', 'active_slot'], 'crm_campaign_dispatch_attempts_active_unique');
        $this->addUniqueIfPossible(self::ATTEMPTS_TABLE, ['crm_campaign_dispatch_execution_batch_id', 'attempt_number'], 'crm_campaign_dispatch_attempts_number_unique');
        $this->addUniqueIfPossible(self::ATTEMPTS_TABLE, ['attempt_idempotency_key'], 'crm_campaign_dispatch_attempts_idem_unique');
    }

    private function ensureRecipientAttemptsTable(): void
    {
        if (!Schema::hasTable(self::RECIPIENT_ATTEMPTS_TABLE)) {
            Schema::create(self::RECIPIENT_ATTEMPTS_TABLE, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('crm_campaign_draft_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_preparation_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_run_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_execution_batch_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_execution_recipient_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_attempt_id');
                $table->unsignedBigInteger('customer_id');
                $table->string('channel', 30);
                $table->string('provider_key', 50);
                $table->string('status', 20)->default('prepared');
                $table->string('recipient_idempotency_key', 64);
                $table->timestamp('created_at')->nullable();

                $table->index('crm_campaign_dispatch_attempt_id', 'crm_campaign_dispatch_rec_attempts_attempt_idx');
                $table->index('crm_campaign_dispatch_execution_batch_id', 'crm_campaign_dispatch_rec_attempts_batch_idx');
                $table->index('customer_id', 'crm_campaign_dispatch_rec_attempts_customer_idx');
                $table->index('status', 'crm_campaign_dispatch_rec_attempts_status_idx');
                $table->unique(['crm_campaign_dispatch_attempt_id', 'crm_campaign_dispatch_execution_recipient_id'], 'crm_campaign_dispatch_rec_attempts_exec_rec_unique');
                $table->unique(['crm_campaign_dispatch_attempt_id', 'customer_id'], 'crm_campaign_dispatch_rec_attempts_customer_unique');
                $table->unique('recipient_idempotency_key', 'crm_campaign_dispatch_rec_attempts_idem_unique');
            });

            return;
        }

        $this->addMissingColumns(self::RECIPIENT_ATTEMPTS_TABLE, [
            'product_website_id' => fn (Blueprint $table) => $table->unsignedBigInteger('product_website_id')->nullable(),
            'crm_campaign_draft_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_draft_id')->nullable(),
            'crm_campaign_dispatch_preparation_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_preparation_id')->nullable(),
            'crm_campaign_dispatch_run_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_run_id')->nullable(),
            'crm_campaign_dispatch_execution_batch_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_execution_batch_id')->nullable(),
            'crm_campaign_dispatch_execution_recipient_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_execution_recipient_id')->nullable(),
            'crm_campaign_dispatch_attempt_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_attempt_id')->nullable(),
            'customer_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_id')->nullable(),
            'channel' => fn (Blueprint $table) => $table->string('channel', 30)->nullable(),
            'provider_key' => fn (Blueprint $table) => $table->string('provider_key', 50)->nullable(),
            'status' => fn (Blueprint $table) => $table->string('status', 20)->default('prepared'),
            'recipient_idempotency_key' => fn (Blueprint $table) => $table->string('recipient_idempotency_key', 64)->nullable(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
        ]);

        $this->addIndexIfPossible(self::RECIPIENT_ATTEMPTS_TABLE, ['crm_campaign_dispatch_attempt_id'], 'crm_campaign_dispatch_rec_attempts_attempt_idx');
        $this->addIndexIfPossible(self::RECIPIENT_ATTEMPTS_TABLE, ['crm_campaign_dispatch_execution_batch_id'], 'crm_campaign_dispatch_rec_attempts_batch_idx');
        $this->addIndexIfPossible(self::RECIPIENT_ATTEMPTS_TABLE, ['customer_id'], 'crm_campaign_dispatch_rec_attempts_customer_idx');
        $this->addIndexIfPossible(self::RECIPIENT_ATTEMPTS_TABLE, ['status'], 'crm_campaign_dispatch_rec_attempts_status_idx');
        $this->addUniqueIfPossible(self::RECIPIENT_ATTEMPTS_TABLE, ['crm_campaign_dispatch_attempt_id', 'crm_campaign_dispatch_execution_recipient_id'], 'crm_campaign_dispatch_rec_attempts_exec_rec_unique');
        $this->addUniqueIfPossible(self::RECIPIENT_ATTEMPTS_TABLE, ['crm_campaign_dispatch_attempt_id', 'customer_id'], 'crm_campaign_dispatch_rec_attempts_customer_unique');
        $this->addUniqueIfPossible(self::RECIPIENT_ATTEMPTS_TABLE, ['recipient_idempotency_key'], 'crm_campaign_dispatch_rec_attempts_idem_unique');
    }

    private function addMissingColumns(string $tableName, array $columns): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn($tableName, $column)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($definition) {
                $definition($table);
            });
        }
    }

    private function addIndexIfPossible(string $tableName, array $columns, string $indexName): void
    {
        if (!$this->hasColumns($tableName, $columns) || $this->hasIndex($tableName, $indexName)) {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        } catch (\Throwable $exception) {
            Log::warning('CRM campaign dispatch attempt index could not be added.', [
                'table' => $tableName,
                'columns' => $columns,
                'index' => $indexName,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function addUniqueIfPossible(string $tableName, array $columns, string $indexName): void
    {
        if (!$this->hasColumns($tableName, $columns) || $this->hasIndex($tableName, $indexName)) {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                $table->unique($columns, $indexName);
            });
        } catch (\Throwable $exception) {
            Log::warning('CRM campaign dispatch attempt unique index could not be added.', [
                'table' => $tableName,
                'columns' => $columns,
                'index' => $indexName,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function hasColumns(string $tableName, array $columns): bool
    {
        if (!Schema::hasTable($tableName)) {
            return false;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($tableName, $column)) {
                return false;
            }
        }

        return true;
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        try {
            return count(DB::select('SHOW INDEX FROM `' . $tableName . '` WHERE Key_name = ?', [$indexName])) > 0;
        } catch (\Throwable $exception) {
            return false;
        }
    }
};
