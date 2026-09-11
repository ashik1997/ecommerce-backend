<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ac_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('ac_transactions', 'ref_service_instance_id')) {
                $table->unsignedBigInteger('ref_service_instance_id')->nullable()->after('ref_customer_payment_id');
                $table->index('ref_service_instance_id', 'act_service_instance_idx');
            }

            if (!Schema::hasColumn('ac_transactions', 'ref_service_payment_id')) {
                $table->unsignedBigInteger('ref_service_payment_id')->nullable()->after('ref_service_instance_id');
                $table->index('ref_service_payment_id', 'act_service_payment_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ac_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('ac_transactions', 'ref_service_payment_id')) {
                $table->dropIndex('act_service_payment_idx');
                $table->dropColumn('ref_service_payment_id');
            }

            if (Schema::hasColumn('ac_transactions', 'ref_service_instance_id')) {
                $table->dropIndex('act_service_instance_idx');
                $table->dropColumn('ref_service_instance_id');
            }
        });
    }
};
