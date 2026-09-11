<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ac_transactions')) {
            return;
        }

        $this->addIndexIfPossible('ac_transactions_transaction_type_idx', ['transaction_type']);
        $this->addIndexIfPossible('ac_transactions_event_type_idx', ['event_type']);
        $this->addIndexIfPossible('ac_transactions_ref_fa_id_idx', ['ref_fixed_asset_id']);
        $this->addIndexIfPossible('ac_transactions_ref_fa_source_idx', ['ref_fixed_asset_source_type', 'ref_fixed_asset_source_id']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('ac_transactions')) {
            return;
        }

        foreach ([
            'ac_transactions_transaction_type_idx',
            'ac_transactions_event_type_idx',
            'ac_transactions_ref_fa_id_idx',
            'ac_transactions_ref_fa_source_idx',
        ] as $index) {
            if ($this->hasIndex($index)) {
                try {
                    Schema::table('ac_transactions', function (Blueprint $table) use ($index) {
                        $table->dropIndex($index);
                    });
                } catch (\Throwable $e) {
                    // Ignore missing index on rollback in older databases.
                }
            }
        }
    }

    private function addIndexIfPossible(string $indexName, array $columns): void
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn('ac_transactions', $column)) {
                return;
            }
        }

        if ($this->hasIndex($indexName)) {
            return;
        }

        try {
            Schema::table('ac_transactions', function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // Keep migration production-safe if an index already exists under a different name.
        }
    }

    private function hasIndex(string $indexName): bool
    {
        try {
            $rows = DB::select('SHOW INDEX FROM ac_transactions WHERE Key_name = ?', [$indexName]);
            return count($rows) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
