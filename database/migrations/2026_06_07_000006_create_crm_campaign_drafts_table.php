<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'crm_campaign_drafts';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->string('name', 160);
                $table->text('description')->nullable();
                $table->string('planned_channel', 30)->nullable();
                $table->string('subject')->nullable();
                $table->text('message_body')->nullable();
                $table->unsignedBigInteger('audience_saved_segment_id')->nullable();
                $table->string('audience_segment_name_snapshot', 160)->nullable();
                $table->string('audience_segment_visibility_snapshot', 20)->nullable();
                $table->json('audience_filters_json');
                $table->timestamp('audience_snapshot_at')->nullable();
                $table->string('visibility', 20)->default('private');
                $table->string('status', 20)->default('draft');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->unsignedBigInteger('archived_by')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();

                $table->index('product_website_id', 'crm_campaign_drafts_website_idx');
                $table->index('audience_saved_segment_id', 'crm_campaign_drafts_segment_idx');
                $table->index('planned_channel', 'crm_campaign_drafts_channel_idx');
                $table->index('visibility', 'crm_campaign_drafts_visibility_idx');
                $table->index('status', 'crm_campaign_drafts_status_idx');
                $table->index('created_by', 'crm_campaign_drafts_created_by_idx');
                $table->index('archived_at', 'crm_campaign_drafts_archived_at_idx');
            });

            return;
        }

        $this->addMissingColumns();
        $this->addIndexIfPossible('product_website_id', 'crm_campaign_drafts_website_idx');
        $this->addIndexIfPossible('audience_saved_segment_id', 'crm_campaign_drafts_segment_idx');
        $this->addIndexIfPossible('planned_channel', 'crm_campaign_drafts_channel_idx');
        $this->addIndexIfPossible('visibility', 'crm_campaign_drafts_visibility_idx');
        $this->addIndexIfPossible('status', 'crm_campaign_drafts_status_idx');
        $this->addIndexIfPossible('created_by', 'crm_campaign_drafts_created_by_idx');
        $this->addIndexIfPossible('archived_at', 'crm_campaign_drafts_archived_at_idx');
    }

    public function down(): void
    {
        // Intentionally non-destructive: campaign drafts are CRM planning records.
    }

    private function addMissingColumns(): void
    {
        $columns = [
            'product_website_id' => fn (Blueprint $table) => $table->unsignedBigInteger('product_website_id')->nullable(),
            'name' => fn (Blueprint $table) => $table->string('name', 160)->nullable(),
            'description' => fn (Blueprint $table) => $table->text('description')->nullable(),
            'planned_channel' => fn (Blueprint $table) => $table->string('planned_channel', 30)->nullable(),
            'subject' => fn (Blueprint $table) => $table->string('subject')->nullable(),
            'message_body' => fn (Blueprint $table) => $table->text('message_body')->nullable(),
            'audience_saved_segment_id' => fn (Blueprint $table) => $table->unsignedBigInteger('audience_saved_segment_id')->nullable(),
            'audience_segment_name_snapshot' => fn (Blueprint $table) => $table->string('audience_segment_name_snapshot', 160)->nullable(),
            'audience_segment_visibility_snapshot' => fn (Blueprint $table) => $table->string('audience_segment_visibility_snapshot', 20)->nullable(),
            'audience_filters_json' => fn (Blueprint $table) => $table->json('audience_filters_json')->nullable(),
            'audience_snapshot_at' => fn (Blueprint $table) => $table->timestamp('audience_snapshot_at')->nullable(),
            'visibility' => fn (Blueprint $table) => $table->string('visibility', 20)->default('private'),
            'status' => fn (Blueprint $table) => $table->string('status', 20)->default('draft'),
            'created_by' => fn (Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable(),
            'updated_by' => fn (Blueprint $table) => $table->unsignedBigInteger('updated_by')->nullable(),
            'archived_by' => fn (Blueprint $table) => $table->unsignedBigInteger('archived_by')->nullable(),
            'archived_at' => fn (Blueprint $table) => $table->timestamp('archived_at')->nullable(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn(self::TABLE, $column)) {
                continue;
            }

            Schema::table(self::TABLE, function (Blueprint $table) use ($definition) {
                $definition($table);
            });
        }
    }

    private function addIndexIfPossible(string $column, string $indexName): void
    {
        if (!Schema::hasColumn(self::TABLE, $column) || $this->hasIndex($indexName)) {
            return;
        }

        try {
            Schema::table(self::TABLE, function (Blueprint $table) use ($column, $indexName) {
                $table->index($column, $indexName);
            });
        } catch (\Throwable $exception) {
            Log::warning('CRM campaign draft index could not be added.', [
                'column' => $column,
                'index' => $indexName,
                'error' => $exception->getMessage(),
            ]);
        }
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
