<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fbm_report_exports')) {
            return;
        }

        Schema::create('fbm_report_exports', function (Blueprint $table) {
            $table->id();
            $table->uuid('export_uuid')->unique();
            $table->string('report_type', 60);
            $table->string('format', 20)->default('csv');
            $table->string('status', 30)->default('completed');
            $table->json('filter_snapshot')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->string('file_disk', 40)->default('local');
            $table->string('file_path', 500)->nullable();
            $table->char('download_token_hash', 64)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->string('safe_error', 500)->nullable();
            $table->timestamps();

            $table->index(['report_type', 'status'], 'fbm_report_exports_type_status_idx');
            $table->index('generated_at', 'fbm_report_exports_generated_idx');
            $table->index('requested_by', 'fbm_report_exports_user_idx');
        });
    }

    public function down(): void
    {
        // Generated export audit rows are retained intentionally.
    }
};
