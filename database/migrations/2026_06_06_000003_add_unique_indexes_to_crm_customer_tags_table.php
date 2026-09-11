<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_customer_tags')) {
            return;
        }

        $this->addUniqueIndexIfPossible('name', 'crm_tags_name_unique');
        $this->addUniqueIndexIfPossible('slug', 'crm_tags_slug_unique');
    }

    public function down(): void
    {
        // Intentionally non-destructive: CRM tag uniqueness guards remain forward-only.
    }

    private function addUniqueIndexIfPossible(string $column, string $indexName): void
    {
        if (!Schema::hasColumn('crm_customer_tags', $column) || $this->hasIndex($indexName)) {
            return;
        }

        if ($this->hasDuplicates($column)) {
            Log::warning('CRM customer tag unique index skipped because duplicate values already exist.', [
                'column' => $column,
                'index' => $indexName,
            ]);

            return;
        }

        try {
            Schema::table('crm_customer_tags', function (Blueprint $table) use ($column, $indexName) {
                $table->unique($column, $indexName);
            });
        } catch (\Throwable $exception) {
            Log::warning('CRM customer tag unique index could not be added.', [
                'column' => $column,
                'index' => $indexName,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function hasDuplicates(string $column): bool
    {
        try {
            return DB::table('crm_customer_tags')
                ->whereNotNull($column)
                ->where($column, '<>', '')
                ->select($column)
                ->groupBy($column)
                ->havingRaw('COUNT(*) > 1')
                ->exists();
        } catch (\Throwable $exception) {
            Log::warning('CRM customer tag duplicate preflight check failed.', [
                'column' => $column,
                'error' => $exception->getMessage(),
            ]);

            return true;
        }
    }

    private function hasIndex(string $indexName): bool
    {
        try {
            return count(DB::select('SHOW INDEX FROM `crm_customer_tags` WHERE Key_name = ?', [$indexName])) > 0;
        } catch (\Throwable $exception) {
            return false;
        }
    }
};
