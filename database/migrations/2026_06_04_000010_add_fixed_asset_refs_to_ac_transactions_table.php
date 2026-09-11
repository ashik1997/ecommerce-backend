<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ac_transactions')) {
            return;
        }

        Schema::table('ac_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('ac_transactions', 'ref_fixed_asset_id')) {
                $table->unsignedBigInteger('ref_fixed_asset_id')->nullable()->after('ref_expense_id')->index();
            }
            if (! Schema::hasColumn('ac_transactions', 'ref_fixed_asset_source_type')) {
                $table->string('ref_fixed_asset_source_type', 80)->nullable()->after('ref_fixed_asset_id')->index();
            }
            if (! Schema::hasColumn('ac_transactions', 'ref_fixed_asset_source_id')) {
                $table->unsignedBigInteger('ref_fixed_asset_source_id')->nullable()->after('ref_fixed_asset_source_type')->index();
            }
            if (! Schema::hasColumn('ac_transactions', 'ref_fixed_asset_reversal_of')) {
                $table->unsignedBigInteger('ref_fixed_asset_reversal_of')->nullable()->after('ref_fixed_asset_source_id')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ac_transactions')) {
            return;
        }

        Schema::table('ac_transactions', function (Blueprint $table) {
            foreach (['ref_fixed_asset_reversal_of', 'ref_fixed_asset_source_id', 'ref_fixed_asset_source_type', 'ref_fixed_asset_id'] as $column) {
                if (Schema::hasColumn('ac_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
