<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'customer_code')) {
                $table->string('customer_code', 80)->nullable()->after('id');
            }
            if (!Schema::hasColumn('customers', 'lifecycle_stage')) {
                $table->string('lifecycle_stage', 40)->nullable()->after('customer_source_type_id');
            }
            if (!Schema::hasColumn('customers', 'assigned_user_id')) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('reference_by');
            }
            if (!Schema::hasColumn('customers', 'last_contact_at')) {
                $table->timestamp('last_contact_at')->nullable()->after('last_transaction');
            }
            if (!Schema::hasColumn('customers', 'next_follow_up_at')) {
                $table->timestamp('next_follow_up_at')->nullable()->after('last_contact_at');
            }
            if (!Schema::hasColumn('customers', 'last_order_at')) {
                $table->timestamp('last_order_at')->nullable()->after('next_follow_up_at');
            }
            if (!Schema::hasColumn('customers', 'total_order_value')) {
                $table->decimal('total_order_value', 16, 2)->default(0)->after('last_order_at');
            }
            if (!Schema::hasColumn('customers', 'total_paid')) {
                $table->decimal('total_paid', 16, 2)->default(0)->after('total_order_value');
            }
            if (!Schema::hasColumn('customers', 'current_due')) {
                $table->decimal('current_due', 16, 2)->default(0)->after('total_paid');
            }
            if (!Schema::hasColumn('customers', 'overdue_amount')) {
                $table->decimal('overdue_amount', 16, 2)->default(0)->after('current_due');
            }
            if (!Schema::hasColumn('customers', 'credit_status')) {
                $table->string('credit_status', 40)->nullable()->after('overdue_amount');
            }
            if (!Schema::hasColumn('customers', 'is_duplicate_candidate')) {
                $table->boolean('is_duplicate_candidate')->default(false)->after('credit_status');
            }
        });

        $this->addIndexIfPossible('customers', ['customer_code'], 'customers_customer_code_idx');
        $this->addIndexIfPossible('customers', ['lifecycle_stage'], 'customers_lifecycle_stage_idx');
        $this->addIndexIfPossible('customers', ['assigned_user_id'], 'customers_assigned_user_idx');
        $this->addIndexIfPossible('customers', ['next_follow_up_at'], 'customers_next_follow_up_idx');
        $this->addIndexIfPossible('customers', ['last_order_at'], 'customers_last_order_idx');
        $this->addIndexIfPossible('customers', ['credit_status'], 'customers_credit_status_idx');
        $this->addIndexIfPossible('customers', ['is_duplicate_candidate'], 'customers_duplicate_candidate_idx');
    }

    public function down(): void
    {
        // Intentionally non-destructive: CRM summary data may be important business data.
    }

    private function addIndexIfPossible(string $tableName, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($tableName, $column)) {
                return;
            }
        }

        if ($this->hasIndex($tableName, $indexName)) {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        } catch (\Throwable $exception) {
            // Keep application upgrades non-blocking if an equivalent legacy index already exists.
        }
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        try {
            $indexes = DB::select('SHOW INDEX FROM `' . str_replace('`', '``', $tableName) . '` WHERE Key_name = ?', [$indexName]);

            return count($indexes) > 0;
        } catch (\Throwable $exception) {
            return false;
        }
    }
};
