<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'crm_campaign_dispatch_recipient_attempt_events';

    public function up(): void
    {
        $this->ensureTable();
    }

    public function down(): void
    {
        // Intentionally non-destructive. Provider exchange audit history must remain append-only.
    }

    private function ensureTable(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('crm_campaign_draft_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_attempt_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_recipient_attempt_id');
                $table->unsignedBigInteger('crm_campaign_dispatch_execution_batch_id');
                $table->string('event_type', 60);
                $table->string('provider_key', 50);
                $table->unsignedInteger('request_sequence')->default(1);
                $table->unsignedTinyInteger('terminal_slot')->nullable();
                $table->string('status', 30);
                $table->string('provider_message_id', 191)->nullable();
                $table->string('provider_response_code', 100)->nullable();
                $table->text('redacted_response_summary')->nullable();
                $table->char('response_hash', 64)->nullable();
                $table->timestamp('request_started_at')->nullable();
                $table->timestamp('request_completed_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->string('failure_category', 60)->nullable();
                $table->text('failure_summary')->nullable();
                $table->json('metadata_json')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index('crm_campaign_dispatch_attempt_id', 'crm_campaign_dispatch_rec_events_attempt_idx');
                $table->index('crm_campaign_dispatch_recipient_attempt_id', 'crm_campaign_dispatch_rec_events_recipient_idx');
                $table->index('crm_campaign_dispatch_execution_batch_id', 'crm_campaign_dispatch_rec_events_batch_idx');
                $table->index('event_type', 'crm_campaign_dispatch_rec_events_type_idx');
                $table->index('status', 'crm_campaign_dispatch_rec_events_status_idx');
                $table->index('failure_category', 'crm_campaign_dispatch_rec_events_failure_idx');
                $table->index('created_at', 'crm_campaign_dispatch_rec_events_created_idx');
                $table->unique(['crm_campaign_dispatch_recipient_attempt_id', 'request_sequence', 'event_type'], 'crm_campaign_dispatch_rec_events_seq_type_unique');
                $table->unique(['crm_campaign_dispatch_recipient_attempt_id', 'request_sequence', 'terminal_slot'], 'crm_campaign_dispatch_rec_events_seq_terminal_unique');
            });

            return;
        }

        $this->addMissingColumns([
            'product_website_id' => fn (Blueprint $table) => $table->unsignedBigInteger('product_website_id')->nullable(),
            'crm_campaign_draft_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_draft_id')->nullable(),
            'crm_campaign_dispatch_attempt_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_attempt_id')->nullable(),
            'crm_campaign_dispatch_recipient_attempt_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_recipient_attempt_id')->nullable(),
            'crm_campaign_dispatch_execution_batch_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_dispatch_execution_batch_id')->nullable(),
            'event_type' => fn (Blueprint $table) => $table->string('event_type', 60)->nullable(),
            'provider_key' => fn (Blueprint $table) => $table->string('provider_key', 50)->nullable(),
            'request_sequence' => fn (Blueprint $table) => $table->unsignedInteger('request_sequence')->default(1),
            'terminal_slot' => fn (Blueprint $table) => $table->unsignedTinyInteger('terminal_slot')->nullable(),
            'status' => fn (Blueprint $table) => $table->string('status', 30)->nullable(),
            'provider_message_id' => fn (Blueprint $table) => $table->string('provider_message_id', 191)->nullable(),
            'provider_response_code' => fn (Blueprint $table) => $table->string('provider_response_code', 100)->nullable(),
            'redacted_response_summary' => fn (Blueprint $table) => $table->text('redacted_response_summary')->nullable(),
            'response_hash' => fn (Blueprint $table) => $table->char('response_hash', 64)->nullable(),
            'request_started_at' => fn (Blueprint $table) => $table->timestamp('request_started_at')->nullable(),
            'request_completed_at' => fn (Blueprint $table) => $table->timestamp('request_completed_at')->nullable(),
            'failed_at' => fn (Blueprint $table) => $table->timestamp('failed_at')->nullable(),
            'failure_category' => fn (Blueprint $table) => $table->string('failure_category', 60)->nullable(),
            'failure_summary' => fn (Blueprint $table) => $table->text('failure_summary')->nullable(),
            'metadata_json' => fn (Blueprint $table) => $table->json('metadata_json')->nullable(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
        ]);

        $this->addIndexIfPossible(['crm_campaign_dispatch_attempt_id'], 'crm_campaign_dispatch_rec_events_attempt_idx');
        $this->addIndexIfPossible(['crm_campaign_dispatch_recipient_attempt_id'], 'crm_campaign_dispatch_rec_events_recipient_idx');
        $this->addIndexIfPossible(['crm_campaign_dispatch_execution_batch_id'], 'crm_campaign_dispatch_rec_events_batch_idx');
        $this->addIndexIfPossible(['event_type'], 'crm_campaign_dispatch_rec_events_type_idx');
        $this->addIndexIfPossible(['status'], 'crm_campaign_dispatch_rec_events_status_idx');
        $this->addIndexIfPossible(['failure_category'], 'crm_campaign_dispatch_rec_events_failure_idx');
        $this->addIndexIfPossible(['created_at'], 'crm_campaign_dispatch_rec_events_created_idx');
        $this->addUniqueIfPossible(['crm_campaign_dispatch_recipient_attempt_id', 'request_sequence', 'event_type'], 'crm_campaign_dispatch_rec_events_seq_type_unique');
        $this->addUniqueIfPossible(['crm_campaign_dispatch_recipient_attempt_id', 'request_sequence', 'terminal_slot'], 'crm_campaign_dispatch_rec_events_seq_terminal_unique');
    }

    private function addMissingColumns(array $columns): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn(self::TABLE, $column)) {
                continue;
            }

            Schema::table(self::TABLE, function (Blueprint $table) use ($definition) {
                $definition($table);
            });
        }
    }

    private function addIndexIfPossible(array $columns, string $indexName): void
    {
        if (!$this->hasColumns($columns) || $this->hasIndex($indexName)) {
            return;
        }

        try {
            Schema::table(self::TABLE, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        } catch (\Throwable $exception) {
            Log::warning('CRM campaign dispatch recipient-attempt event index could not be added.', [
                'table' => self::TABLE,
                'columns' => $columns,
                'index' => $indexName,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function addUniqueIfPossible(array $columns, string $indexName): void
    {
        if (!$this->hasColumns($columns) || $this->hasIndex($indexName)) {
            return;
        }

        try {
            Schema::table(self::TABLE, function (Blueprint $table) use ($columns, $indexName) {
                $table->unique($columns, $indexName);
            });
        } catch (\Throwable $exception) {
            Log::warning('CRM campaign dispatch recipient-attempt event unique index could not be added.', [
                'table' => self::TABLE,
                'columns' => $columns,
                'index' => $indexName,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function hasColumns(array $columns): bool
    {
        if (!Schema::hasTable(self::TABLE)) {
            return false;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn(self::TABLE, $column)) {
                return false;
            }
        }

        return true;
    }

    private function hasIndex(string $indexName): bool
    {
        try {
            return count(DB::select('SHOW INDEX FROM `' . self::TABLE . '` WHERE Key_name = ?', [$indexName])) > 0;
        } catch (\Throwable $exception) {
            return false;
        }
    }
};
