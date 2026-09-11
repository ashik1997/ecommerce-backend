<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const RUNS_TABLE = 'crm_campaign_dispatch_runs';
    private const BATCHES_TABLE = 'crm_campaign_dispatch_execution_batches';
    private const RECIPIENTS_TABLE = 'crm_campaign_dispatch_execution_recipients';

    public function up(): void
    {
        $this->addRunClaimColumns();
        $this->ensureExecutionBatchesTable();
        $this->ensureExecutionRecipientsTable();
    }

    public function down(): void
    {
        // Intentionally non-destructive: execution-claim ledgers and audit metadata are retained.
    }

    private function addRunClaimColumns(): void
    {
        $this->addMissingColumns(self::RUNS_TABLE, [
            'claimed_execution_batch_id' => fn (Blueprint $table) => $table->unsignedBigInteger('claimed_execution_batch_id')->nullable(),
            'claimed_by' => fn (Blueprint $table) => $table->unsignedBigInteger('claimed_by')->nullable(),
            'claimed_at' => fn (Blueprint $table) => $table->timestamp('claimed_at')->nullable(),
        ]);

        $this->addUniqueIfPossible(self::RUNS_TABLE, ['claimed_execution_batch_id'], 'crm_campaign_dispatch_runs_claimed_batch_unique');
        $this->addIndexIfPossible(self::RUNS_TABLE, ['claimed_by'], 'crm_campaign_dispatch_runs_claimed_by_idx');
        $this->addIndexIfPossible(self::RUNS_TABLE, ['claimed_at'], 'crm_campaign_dispatch_runs_claimed_at_idx');
    }

    private function ensureExecutionBatchesTable(): void
    {
        if (!Schema::hasTable(self::BATCHES_TABLE)) {
            Schema::create(self::BATCHES_TABLE, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('crm_campaign_draft_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_preparation_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_run_id');
                $table->string('status', 20)->default('prepared');
                $table->unsignedTinyInteger('active_slot')->nullable();
                $table->string('channel', 30);
                $table->string('approved_snapshot_signature', 64);
                $table->unsignedSmallInteger('approved_snapshot_signature_version');
                $table->timestamp('approved_snapshot_at');
                $table->string('approved_recipient_set_signature', 64);
                $table->unsignedSmallInteger('approved_recipient_set_signature_version');
                $table->unsignedBigInteger('approved_recipient_count');
                $table->string('frozen_recipient_set_signature', 64);
                $table->unsignedSmallInteger('frozen_recipient_set_signature_version');
                $table->unsignedBigInteger('frozen_recipient_count');
                $table->string('run_integrity_signature', 64);
                $table->unsignedSmallInteger('run_integrity_signature_version');
                $table->text('subject_snapshot')->nullable();
                $table->longText('message_body_snapshot')->nullable();
                $table->string('batch_idempotency_key', 64);
                $table->string('execution_integrity_signature', 64)->nullable();
                $table->unsignedSmallInteger('execution_integrity_signature_version')->nullable();
                $table->unsignedBigInteger('claimed_by')->nullable();
                $table->timestamp('claimed_at')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->json('metadata_json')->nullable();
                $table->timestamps();

                $table->index('product_website_id', 'crm_campaign_dispatch_exec_batches_website_idx');
                $table->index('crm_campaign_draft_id', 'crm_campaign_dispatch_exec_batches_draft_idx');
                $table->index('status', 'crm_campaign_dispatch_exec_batches_status_idx');
                $table->index('claimed_by', 'crm_campaign_dispatch_exec_batches_claimed_by_idx');
                $table->index('claimed_at', 'crm_campaign_dispatch_exec_batches_claimed_at_idx');
                $table->unique('crm_campaign_dispatch_run_id', 'crm_campaign_dispatch_exec_batches_run_unique');
                $table->unique(['crm_campaign_draft_id', 'active_slot'], 'crm_campaign_dispatch_exec_batches_active_unique');
                $table->unique('batch_idempotency_key', 'crm_campaign_dispatch_exec_batches_idem_unique');
            });

            return;
        }

        $this->addMissingColumns(self::BATCHES_TABLE, [
            'product_website_id' => fn (Blueprint $table) => $table->unsignedBigInteger('product_website_id')->nullable(),
            'crm_campaign_draft_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_draft_id')->nullable(),
            'crm_campaign_dispatch_preparation_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_preparation_id')->nullable(),
            'crm_campaign_dispatch_run_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_run_id')->nullable(),
            'status' => fn (Blueprint $table) => $table->string('status', 20)->default('prepared'),
            'active_slot' => fn (Blueprint $table) => $table->unsignedTinyInteger('active_slot')->nullable(),
            'channel' => fn (Blueprint $table) => $table->string('channel', 30)->nullable(),
            'approved_snapshot_signature' => fn (Blueprint $table) => $table->string('approved_snapshot_signature', 64)->nullable(),
            'approved_snapshot_signature_version' => fn (Blueprint $table) => $table->unsignedSmallInteger('approved_snapshot_signature_version')->nullable(),
            'approved_snapshot_at' => fn (Blueprint $table) => $table->timestamp('approved_snapshot_at')->nullable(),
            'approved_recipient_set_signature' => fn (Blueprint $table) => $table->string('approved_recipient_set_signature', 64)->nullable(),
            'approved_recipient_set_signature_version' => fn (Blueprint $table) => $table->unsignedSmallInteger('approved_recipient_set_signature_version')->nullable(),
            'approved_recipient_count' => fn (Blueprint $table) => $table->unsignedBigInteger('approved_recipient_count')->nullable(),
            'frozen_recipient_set_signature' => fn (Blueprint $table) => $table->string('frozen_recipient_set_signature', 64)->nullable(),
            'frozen_recipient_set_signature_version' => fn (Blueprint $table) => $table->unsignedSmallInteger('frozen_recipient_set_signature_version')->nullable(),
            'frozen_recipient_count' => fn (Blueprint $table) => $table->unsignedBigInteger('frozen_recipient_count')->nullable(),
            'run_integrity_signature' => fn (Blueprint $table) => $table->string('run_integrity_signature', 64)->nullable(),
            'run_integrity_signature_version' => fn (Blueprint $table) => $table->unsignedSmallInteger('run_integrity_signature_version')->nullable(),
            'subject_snapshot' => fn (Blueprint $table) => $table->text('subject_snapshot')->nullable(),
            'message_body_snapshot' => fn (Blueprint $table) => $table->longText('message_body_snapshot')->nullable(),
            'batch_idempotency_key' => fn (Blueprint $table) => $table->string('batch_idempotency_key', 64)->nullable(),
            'execution_integrity_signature' => fn (Blueprint $table) => $table->string('execution_integrity_signature', 64)->nullable(),
            'execution_integrity_signature_version' => fn (Blueprint $table) => $table->unsignedSmallInteger('execution_integrity_signature_version')->nullable(),
            'claimed_by' => fn (Blueprint $table) => $table->unsignedBigInteger('claimed_by')->nullable(),
            'claimed_at' => fn (Blueprint $table) => $table->timestamp('claimed_at')->nullable(),
            'cancelled_by' => fn (Blueprint $table) => $table->unsignedBigInteger('cancelled_by')->nullable(),
            'cancelled_at' => fn (Blueprint $table) => $table->timestamp('cancelled_at')->nullable(),
            'cancellation_reason' => fn (Blueprint $table) => $table->text('cancellation_reason')->nullable(),
            'metadata_json' => fn (Blueprint $table) => $table->json('metadata_json')->nullable(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ]);

        $this->addIndexIfPossible(self::BATCHES_TABLE, ['product_website_id'], 'crm_campaign_dispatch_exec_batches_website_idx');
        $this->addIndexIfPossible(self::BATCHES_TABLE, ['crm_campaign_draft_id'], 'crm_campaign_dispatch_exec_batches_draft_idx');
        $this->addIndexIfPossible(self::BATCHES_TABLE, ['status'], 'crm_campaign_dispatch_exec_batches_status_idx');
        $this->addIndexIfPossible(self::BATCHES_TABLE, ['claimed_by'], 'crm_campaign_dispatch_exec_batches_claimed_by_idx');
        $this->addIndexIfPossible(self::BATCHES_TABLE, ['claimed_at'], 'crm_campaign_dispatch_exec_batches_claimed_at_idx');
        $this->addUniqueIfPossible(self::BATCHES_TABLE, ['crm_campaign_dispatch_run_id'], 'crm_campaign_dispatch_exec_batches_run_unique');
        $this->addUniqueIfPossible(self::BATCHES_TABLE, ['crm_campaign_draft_id', 'active_slot'], 'crm_campaign_dispatch_exec_batches_active_unique');
        $this->addUniqueIfPossible(self::BATCHES_TABLE, ['batch_idempotency_key'], 'crm_campaign_dispatch_exec_batches_idem_unique');
    }

    private function ensureExecutionRecipientsTable(): void
    {
        if (!Schema::hasTable(self::RECIPIENTS_TABLE)) {
            Schema::create(self::RECIPIENTS_TABLE, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('crm_campaign_draft_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_preparation_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_run_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_execution_batch_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_run_recipient_id');
                $table->unsignedBigInteger('customer_id');
                $table->string('channel', 30);
                $table->string('destination_hash', 64);
                $table->string('idempotency_key', 64);
                $table->string('status', 20)->default('prepared');
                $table->timestamp('created_at')->nullable();

                $table->index('crm_campaign_draft_id', 'crm_campaign_dispatch_exec_recipients_draft_idx');
                $table->index('crm_campaign_dispatch_run_id', 'crm_campaign_dispatch_exec_recipients_run_idx');
                $table->index('crm_campaign_dispatch_execution_batch_id', 'crm_campaign_dispatch_exec_recipients_batch_idx');
                $table->index('customer_id', 'crm_campaign_dispatch_exec_recipients_customer_idx');
                $table->index('status', 'crm_campaign_dispatch_exec_recipients_status_idx');
                $table->unique('crm_campaign_dispatch_run_recipient_id', 'crm_campaign_dispatch_exec_recipients_source_unique');
                $table->unique(['crm_campaign_dispatch_execution_batch_id', 'customer_id'], 'crm_campaign_dispatch_exec_recipients_customer_unique');
                $table->unique(['crm_campaign_dispatch_execution_batch_id', 'destination_hash'], 'crm_campaign_dispatch_exec_recipients_destination_unique');
                $table->unique('idempotency_key', 'crm_campaign_dispatch_exec_recipients_idem_unique');
            });

            return;
        }

        $this->addMissingColumns(self::RECIPIENTS_TABLE, [
            'product_website_id' => fn (Blueprint $table) => $table->unsignedBigInteger('product_website_id')->nullable(),
            'crm_campaign_draft_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_draft_id')->nullable(),
            'crm_campaign_dispatch_preparation_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_preparation_id')->nullable(),
            'crm_campaign_dispatch_run_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_run_id')->nullable(),
            'crm_campaign_dispatch_execution_batch_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_execution_batch_id')->nullable(),
            'crm_campaign_dispatch_run_recipient_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_run_recipient_id')->nullable(),
            'customer_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_id')->nullable(),
            'channel' => fn (Blueprint $table) => $table->string('channel', 30)->nullable(),
            'destination_hash' => fn (Blueprint $table) => $table->string('destination_hash', 64)->nullable(),
            'idempotency_key' => fn (Blueprint $table) => $table->string('idempotency_key', 64)->nullable(),
            'status' => fn (Blueprint $table) => $table->string('status', 20)->default('prepared'),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
        ]);

        $this->addIndexIfPossible(self::RECIPIENTS_TABLE, ['crm_campaign_draft_id'], 'crm_campaign_dispatch_exec_recipients_draft_idx');
        $this->addIndexIfPossible(self::RECIPIENTS_TABLE, ['crm_campaign_dispatch_run_id'], 'crm_campaign_dispatch_exec_recipients_run_idx');
        $this->addIndexIfPossible(self::RECIPIENTS_TABLE, ['crm_campaign_dispatch_execution_batch_id'], 'crm_campaign_dispatch_exec_recipients_batch_idx');
        $this->addIndexIfPossible(self::RECIPIENTS_TABLE, ['customer_id'], 'crm_campaign_dispatch_exec_recipients_customer_idx');
        $this->addIndexIfPossible(self::RECIPIENTS_TABLE, ['status'], 'crm_campaign_dispatch_exec_recipients_status_idx');
        $this->addUniqueIfPossible(self::RECIPIENTS_TABLE, ['crm_campaign_dispatch_run_recipient_id'], 'crm_campaign_dispatch_exec_recipients_source_unique');
        $this->addUniqueIfPossible(self::RECIPIENTS_TABLE, ['crm_campaign_dispatch_execution_batch_id', 'customer_id'], 'crm_campaign_dispatch_exec_recipients_customer_unique');
        $this->addUniqueIfPossible(self::RECIPIENTS_TABLE, ['crm_campaign_dispatch_execution_batch_id', 'destination_hash'], 'crm_campaign_dispatch_exec_recipients_destination_unique');
        $this->addUniqueIfPossible(self::RECIPIENTS_TABLE, ['idempotency_key'], 'crm_campaign_dispatch_exec_recipients_idem_unique');
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
            Log::warning('CRM campaign dispatch execution index could not be added.', [
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
            Log::warning('CRM campaign dispatch execution unique index could not be added.', [
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
