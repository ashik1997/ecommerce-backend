<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DRAFTS_TABLE = 'crm_campaign_drafts';
    private const HISTORY_TABLE = 'crm_campaign_draft_approval_history';
    private const PREPARATIONS_TABLE = 'crm_campaign_dispatch_preparations';
    private const RECIPIENTS_TABLE = 'crm_campaign_dispatch_recipients';

    public function up(): void
    {
        $this->addApprovalRecipientSealColumns();
        $this->ensurePreparationsTable();
        $this->ensureRecipientsTable();
    }

    public function down(): void
    {
        // Intentionally non-destructive: frozen dispatch-preparation records and audit metadata are retained.
    }

    private function addApprovalRecipientSealColumns(): void
    {
        $this->addMissingColumns(self::DRAFTS_TABLE, [
            'approved_recipient_set_signature' => fn (Blueprint $table) => $table->string('approved_recipient_set_signature', 64)->nullable(),
            'approved_recipient_set_signature_version' => fn (Blueprint $table) => $table->unsignedSmallInteger('approved_recipient_set_signature_version')->nullable(),
            'approved_recipient_count' => fn (Blueprint $table) => $table->unsignedBigInteger('approved_recipient_count')->nullable(),
        ]);

        $this->addMissingColumns(self::HISTORY_TABLE, [
            'approved_recipient_set_signature' => fn (Blueprint $table) => $table->string('approved_recipient_set_signature', 64)->nullable(),
            'approved_recipient_set_signature_version' => fn (Blueprint $table) => $table->unsignedSmallInteger('approved_recipient_set_signature_version')->nullable(),
            'approved_recipient_count' => fn (Blueprint $table) => $table->unsignedBigInteger('approved_recipient_count')->nullable(),
        ]);
    }

    private function ensurePreparationsTable(): void
    {
        if (!Schema::hasTable(self::PREPARATIONS_TABLE)) {
            Schema::create(self::PREPARATIONS_TABLE, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('crm_campaign_draft_id');
                $table->string('status', 20)->default('prepared');
                $table->unsignedTinyInteger('active_slot')->nullable();
                $table->string('channel', 30);
                $table->string('approved_snapshot_signature', 64);
                $table->unsignedSmallInteger('approved_snapshot_signature_version');
                $table->timestamp('approved_snapshot_at');
                $table->string('approved_recipient_set_signature', 64);
                $table->unsignedSmallInteger('approved_recipient_set_signature_version');
                $table->unsignedBigInteger('approved_recipient_count');
                $table->string('frozen_recipient_set_signature', 64)->nullable();
                $table->unsignedSmallInteger('frozen_recipient_set_signature_version')->nullable();
                $table->unsignedBigInteger('frozen_recipient_count')->default(0);
                $table->unsignedBigInteger('prepared_by')->nullable();
                $table->timestamp('prepared_at')->nullable();
                $table->unsignedBigInteger('invalidated_by')->nullable();
                $table->timestamp('invalidated_at')->nullable();
                $table->text('invalidation_reason')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->json('metadata_json')->nullable();
                $table->timestamps();

                $table->index('product_website_id', 'crm_campaign_dispatch_preparations_website_idx');
                $table->index('crm_campaign_draft_id', 'crm_campaign_dispatch_preparations_draft_idx');
                $table->index('status', 'crm_campaign_dispatch_preparations_status_idx');
                $table->index('prepared_by', 'crm_campaign_dispatch_preparations_prepared_by_idx');
                $table->index('prepared_at', 'crm_campaign_dispatch_preparations_prepared_at_idx');
                $table->unique(['crm_campaign_draft_id', 'active_slot'], 'crm_campaign_dispatch_preparations_active_unique');
            });

            return;
        }

        $this->addMissingColumns(self::PREPARATIONS_TABLE, [
            'product_website_id' => fn (Blueprint $table) => $table->unsignedBigInteger('product_website_id')->nullable(),
            'crm_campaign_draft_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_draft_id')->nullable(),
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
            'frozen_recipient_count' => fn (Blueprint $table) => $table->unsignedBigInteger('frozen_recipient_count')->default(0),
            'prepared_by' => fn (Blueprint $table) => $table->unsignedBigInteger('prepared_by')->nullable(),
            'prepared_at' => fn (Blueprint $table) => $table->timestamp('prepared_at')->nullable(),
            'invalidated_by' => fn (Blueprint $table) => $table->unsignedBigInteger('invalidated_by')->nullable(),
            'invalidated_at' => fn (Blueprint $table) => $table->timestamp('invalidated_at')->nullable(),
            'invalidation_reason' => fn (Blueprint $table) => $table->text('invalidation_reason')->nullable(),
            'cancelled_by' => fn (Blueprint $table) => $table->unsignedBigInteger('cancelled_by')->nullable(),
            'cancelled_at' => fn (Blueprint $table) => $table->timestamp('cancelled_at')->nullable(),
            'cancellation_reason' => fn (Blueprint $table) => $table->text('cancellation_reason')->nullable(),
            'metadata_json' => fn (Blueprint $table) => $table->json('metadata_json')->nullable(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ]);

        $this->addIndexIfPossible(self::PREPARATIONS_TABLE, ['product_website_id'], 'crm_campaign_dispatch_preparations_website_idx');
        $this->addIndexIfPossible(self::PREPARATIONS_TABLE, ['crm_campaign_draft_id'], 'crm_campaign_dispatch_preparations_draft_idx');
        $this->addIndexIfPossible(self::PREPARATIONS_TABLE, ['status'], 'crm_campaign_dispatch_preparations_status_idx');
        $this->addIndexIfPossible(self::PREPARATIONS_TABLE, ['prepared_by'], 'crm_campaign_dispatch_preparations_prepared_by_idx');
        $this->addIndexIfPossible(self::PREPARATIONS_TABLE, ['prepared_at'], 'crm_campaign_dispatch_preparations_prepared_at_idx');
        $this->addUniqueIfPossible(self::PREPARATIONS_TABLE, ['crm_campaign_draft_id', 'active_slot'], 'crm_campaign_dispatch_preparations_active_unique');
    }

    private function ensureRecipientsTable(): void
    {
        if (!Schema::hasTable(self::RECIPIENTS_TABLE)) {
            Schema::create(self::RECIPIENTS_TABLE, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('crm_campaign_draft_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_preparation_id');
                $table->unsignedBigInteger('customer_id');
                $table->string('channel', 30);
                $table->text('destination_ciphertext');
                $table->string('destination_hash', 64);
                $table->timestamp('created_at')->nullable();

                $table->index('crm_campaign_draft_id', 'crm_campaign_dispatch_recipients_draft_idx');
                $table->index('crm_campaign_dispatch_preparation_id', 'crm_campaign_dispatch_recipients_preparation_idx');
                $table->index('customer_id', 'crm_campaign_dispatch_recipients_customer_idx');
                $table->unique(['crm_campaign_dispatch_preparation_id', 'customer_id'], 'crm_campaign_dispatch_recipients_customer_unique');
                $table->unique(['crm_campaign_dispatch_preparation_id', 'destination_hash'], 'crm_campaign_dispatch_recipients_destination_unique');
            });

            return;
        }

        $this->addMissingColumns(self::RECIPIENTS_TABLE, [
            'product_website_id' => fn (Blueprint $table) => $table->unsignedBigInteger('product_website_id')->nullable(),
            'crm_campaign_draft_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_draft_id')->nullable(),
            'crm_campaign_dispatch_preparation_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_preparation_id')->nullable(),
            'customer_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_id')->nullable(),
            'channel' => fn (Blueprint $table) => $table->string('channel', 30)->nullable(),
            'destination_ciphertext' => fn (Blueprint $table) => $table->text('destination_ciphertext')->nullable(),
            'destination_hash' => fn (Blueprint $table) => $table->string('destination_hash', 64)->nullable(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
        ]);

        $this->addIndexIfPossible(self::RECIPIENTS_TABLE, ['crm_campaign_draft_id'], 'crm_campaign_dispatch_recipients_draft_idx');
        $this->addIndexIfPossible(self::RECIPIENTS_TABLE, ['crm_campaign_dispatch_preparation_id'], 'crm_campaign_dispatch_recipients_preparation_idx');
        $this->addIndexIfPossible(self::RECIPIENTS_TABLE, ['customer_id'], 'crm_campaign_dispatch_recipients_customer_idx');
        $this->addUniqueIfPossible(self::RECIPIENTS_TABLE, ['crm_campaign_dispatch_preparation_id', 'customer_id'], 'crm_campaign_dispatch_recipients_customer_unique');
        $this->addUniqueIfPossible(self::RECIPIENTS_TABLE, ['crm_campaign_dispatch_preparation_id', 'destination_hash'], 'crm_campaign_dispatch_recipients_destination_unique');
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
            Log::warning('CRM campaign dispatch-preparation index could not be added.', [
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
            Log::warning('CRM campaign dispatch-preparation unique index could not be added.', [
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
