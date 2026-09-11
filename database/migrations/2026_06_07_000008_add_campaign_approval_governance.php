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

    public function up(): void
    {
        $this->addDraftGovernanceColumns();
        $this->ensureHistoryTable();
    }

    public function down(): void
    {
        // Intentionally non-destructive: campaign approval metadata and immutable audit history are retained.
    }

    private function addDraftGovernanceColumns(): void
    {
        if (!Schema::hasTable(self::DRAFTS_TABLE)) {
            return;
        }

        $columns = [
            'submitted_by' => fn (Blueprint $table) => $table->unsignedBigInteger('submitted_by')->nullable(),
            'submitted_at' => fn (Blueprint $table) => $table->timestamp('submitted_at')->nullable(),
            'reviewed_by' => fn (Blueprint $table) => $table->unsignedBigInteger('reviewed_by')->nullable(),
            'reviewed_at' => fn (Blueprint $table) => $table->timestamp('reviewed_at')->nullable(),
            'review_decision' => fn (Blueprint $table) => $table->string('review_decision', 20)->nullable(),
            'review_note' => fn (Blueprint $table) => $table->text('review_note')->nullable(),
            'approved_snapshot_signature' => fn (Blueprint $table) => $table->string('approved_snapshot_signature', 64)->nullable(),
            'approved_snapshot_signature_version' => fn (Blueprint $table) => $table->unsignedSmallInteger('approved_snapshot_signature_version')->nullable(),
            'approved_snapshot_at' => fn (Blueprint $table) => $table->timestamp('approved_snapshot_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn(self::DRAFTS_TABLE, $column)) {
                continue;
            }

            Schema::table(self::DRAFTS_TABLE, function (Blueprint $table) use ($definition) {
                $definition($table);
            });
        }

        $this->addIndexIfPossible(self::DRAFTS_TABLE, 'submitted_by', 'crm_campaign_drafts_submitted_by_idx');
        $this->addIndexIfPossible(self::DRAFTS_TABLE, 'reviewed_by', 'crm_campaign_drafts_reviewed_by_idx');
        $this->addIndexIfPossible(self::DRAFTS_TABLE, 'reviewed_at', 'crm_campaign_drafts_reviewed_at_idx');
    }

    private function ensureHistoryTable(): void
    {
        if (!Schema::hasTable(self::HISTORY_TABLE)) {
            Schema::create(self::HISTORY_TABLE, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('crm_campaign_draft_id');
                $table->string('action', 40);
                $table->string('from_status', 20)->nullable();
                $table->string('to_status', 20);
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->text('review_note')->nullable();
                $table->string('audience_snapshot_signature', 64)->nullable();
                $table->unsignedSmallInteger('audience_snapshot_signature_version')->nullable();
                $table->string('approved_snapshot_signature', 64)->nullable();
                $table->unsignedSmallInteger('approved_snapshot_signature_version')->nullable();
                $table->timestamp('approved_snapshot_at')->nullable();
                $table->json('metadata_json')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index('crm_campaign_draft_id', 'crm_campaign_approval_history_draft_idx');
                $table->index('actor_id', 'crm_campaign_approval_history_actor_idx');
                $table->index('action', 'crm_campaign_approval_history_action_idx');
                $table->index('created_at', 'crm_campaign_approval_history_created_at_idx');
            });

            return;
        }

        $columns = [
            'product_website_id' => fn (Blueprint $table) => $table->unsignedBigInteger('product_website_id')->nullable(),
            'crm_campaign_draft_id' => fn (Blueprint $table) => $table->unsignedBigInteger('crm_campaign_draft_id')->nullable(),
            'action' => fn (Blueprint $table) => $table->string('action', 40)->nullable(),
            'from_status' => fn (Blueprint $table) => $table->string('from_status', 20)->nullable(),
            'to_status' => fn (Blueprint $table) => $table->string('to_status', 20)->nullable(),
            'actor_id' => fn (Blueprint $table) => $table->unsignedBigInteger('actor_id')->nullable(),
            'review_note' => fn (Blueprint $table) => $table->text('review_note')->nullable(),
            'audience_snapshot_signature' => fn (Blueprint $table) => $table->string('audience_snapshot_signature', 64)->nullable(),
            'audience_snapshot_signature_version' => fn (Blueprint $table) => $table->unsignedSmallInteger('audience_snapshot_signature_version')->nullable(),
            'approved_snapshot_signature' => fn (Blueprint $table) => $table->string('approved_snapshot_signature', 64)->nullable(),
            'approved_snapshot_signature_version' => fn (Blueprint $table) => $table->unsignedSmallInteger('approved_snapshot_signature_version')->nullable(),
            'approved_snapshot_at' => fn (Blueprint $table) => $table->timestamp('approved_snapshot_at')->nullable(),
            'metadata_json' => fn (Blueprint $table) => $table->json('metadata_json')->nullable(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn(self::HISTORY_TABLE, $column)) {
                continue;
            }

            Schema::table(self::HISTORY_TABLE, function (Blueprint $table) use ($definition) {
                $definition($table);
            });
        }

        $this->addIndexIfPossible(self::HISTORY_TABLE, 'crm_campaign_draft_id', 'crm_campaign_approval_history_draft_idx');
        $this->addIndexIfPossible(self::HISTORY_TABLE, 'actor_id', 'crm_campaign_approval_history_actor_idx');
        $this->addIndexIfPossible(self::HISTORY_TABLE, 'action', 'crm_campaign_approval_history_action_idx');
        $this->addIndexIfPossible(self::HISTORY_TABLE, 'created_at', 'crm_campaign_approval_history_created_at_idx');
    }

    private function addIndexIfPossible(string $tableName, string $column, string $indexName): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $column) || $this->hasIndex($tableName, $indexName)) {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($column, $indexName) {
                $table->index($column, $indexName);
            });
        } catch (\Throwable $exception) {
            Log::warning('CRM campaign approval governance index could not be added.', [
                'table' => $tableName,
                'column' => $column,
                'index' => $indexName,
                'error' => $exception->getMessage(),
            ]);
        }
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
