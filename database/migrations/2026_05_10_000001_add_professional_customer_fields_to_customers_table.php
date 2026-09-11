<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'customer_type')) {
                $table->string('customer_type', 20)->default('person')->after('customer_source_type_id')->index();
            }
            if (!Schema::hasColumn('customers', 'company_name')) {
                $table->string('company_name')->nullable()->after('full_name');
            }
            if (!Schema::hasColumn('customers', 'trade_name')) {
                $table->string('trade_name')->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('customers', 'bin_no')) {
                $table->string('bin_no', 100)->nullable()->after('trade_name');
            }
            if (!Schema::hasColumn('customers', 'tin_no')) {
                $table->string('tin_no', 100)->nullable()->after('bin_no');
            }
            if (!Schema::hasColumn('customers', 'credit_limit')) {
                $table->decimal('credit_limit', 16, 2)->default(0)->after('available_advance');
            }
            if (!Schema::hasColumn('customers', 'payment_terms_days')) {
                $table->unsignedInteger('payment_terms_days')->nullable()->after('credit_limit');
            }
            if (!Schema::hasColumn('customers', 'allow_due')) {
                $table->boolean('allow_due')->default(true)->after('payment_terms_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            foreach ([
                'allow_due',
                'payment_terms_days',
                'credit_limit',
                'tin_no',
                'bin_no',
                'trade_name',
                'company_name',
                'customer_type',
            ] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

