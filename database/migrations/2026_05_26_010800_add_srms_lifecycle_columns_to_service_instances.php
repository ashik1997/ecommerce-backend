<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('srms_service_instances')) {
            return;
        }

        Schema::table('srms_service_instances', function (Blueprint $table) {
            if (!Schema::hasColumn('srms_service_instances', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('srms_service_instances', 'billed_at')) {
                $table->timestamp('billed_at')->nullable()->after('confirmed_at');
            }

            if (!Schema::hasColumn('srms_service_instances', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('billed_at');
            }

            if (!Schema::hasColumn('srms_service_instances', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('closed_at');
            }

            if (!Schema::hasColumn('srms_service_instances', 'rental_returned_at')) {
                $table->timestamp('rental_returned_at')->nullable()->after('cancelled_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('srms_service_instances')) {
            return;
        }

        Schema::table('srms_service_instances', function (Blueprint $table) {
            foreach (['rental_returned_at', 'cancelled_at', 'closed_at', 'billed_at', 'confirmed_at'] as $column) {
                if (Schema::hasColumn('srms_service_instances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
