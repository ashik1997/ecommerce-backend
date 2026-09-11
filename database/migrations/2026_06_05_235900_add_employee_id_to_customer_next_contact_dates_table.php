<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_next_contact_dates')
            || Schema::hasColumn('customer_next_contact_dates', 'employee_id')) {
            return;
        }

        Schema::table('customer_next_contact_dates', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('customer_id');
            $table->index('employee_id', 'customer_next_contact_employee_idx');
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: preserve scheduled-contact assignments.
    }
};
