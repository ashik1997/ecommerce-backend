<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->reportRuns();
        $this->dailySnapshots();
    }

    public function down(): void
    {
        Schema::dropIfExists('fbm_insight_daily_snapshots');
        Schema::dropIfExists('fbm_insight_report_runs');
    }

    private function reportRuns(): void
    {
        if (Schema::hasTable('fbm_insight_report_runs')) {
            return;
        }

        Schema::create('fbm_insight_report_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('report_uuid')->unique();
            $table->unsignedBigInteger('fbm_sync_run_id')->nullable();
            $table->unsignedBigInteger('fbm_connection_id');
            $table->unsignedBigInteger('fbm_ad_account_id');
            $table->string('insight_level', 30);
            $table->date('window_start');
            $table->date('window_end');
            $table->string('execution_mode', 20);
            $table->string('status', 30);
            $table->string('provider_report_run_key', 190)->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->unsignedInteger('poll_count')->default(0);
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('upserted_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->timestamp('next_poll_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('redacted_message', 500)->nullable();
            $table->json('safe_summary')->nullable();
            $table->timestamps();
            $table->index(['fbm_connection_id', 'status', 'created_at'], 'fbm_insight_run_connection_status_idx');
            $table->index(['fbm_ad_account_id', 'insight_level', 'window_start', 'window_end'], 'fbm_insight_run_account_window_idx');
            $table->index(['execution_mode', 'status', 'next_poll_at'], 'fbm_insight_run_mode_poll_idx');
            $table->index('fbm_sync_run_id', 'fbm_insight_run_sync_idx');
        });
    }

    private function dailySnapshots(): void
    {
        if (Schema::hasTable('fbm_insight_daily_snapshots')) {
            return;
        }

        Schema::create('fbm_insight_daily_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fbm_connection_id');
            $table->unsignedBigInteger('fbm_ad_account_id');
            $table->unsignedBigInteger('fbm_campaign_id')->nullable();
            $table->unsignedBigInteger('fbm_ad_set_id')->nullable();
            $table->unsignedBigInteger('fbm_ad_id')->nullable();
            $table->string('insight_level', 30);
            $table->string('entity_provider_sync_key', 190);
            $table->date('snapshot_date');
            $table->string('account_currency', 20)->nullable();
            $table->string('attribution_setting', 120)->nullable();
            $table->decimal('spend', 20, 6)->default(0);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('inline_link_clicks')->default(0);
            $table->decimal('frequency', 20, 6)->default(0);
            $table->decimal('ctr', 20, 6)->default(0);
            $table->decimal('cpc', 20, 6)->default(0);
            $table->decimal('cpm', 20, 6)->default(0);
            $table->decimal('meta_result_count', 20, 6)->default(0);
            $table->decimal('meta_result_value', 20, 6)->default(0);
            $table->decimal('meta_purchase_count', 20, 6)->default(0);
            $table->decimal('meta_purchase_value', 20, 6)->default(0);
            $table->json('action_metrics')->nullable();
            $table->json('action_value_metrics')->nullable();
            $table->unsignedBigInteger('source_report_run_id')->nullable();
            $table->unsignedBigInteger('source_sync_run_id')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->date('freshness_watermark')->nullable();
            $table->char('metrics_hash', 64);
            $table->timestamps();
            $table->unique(
                ['fbm_connection_id', 'fbm_ad_account_id', 'insight_level', 'entity_provider_sync_key', 'snapshot_date'],
                'fbm_insight_daily_entity_unique'
            );
            $table->index(['fbm_ad_account_id', 'snapshot_date'], 'fbm_insight_daily_account_date_idx');
            $table->index(['fbm_campaign_id', 'snapshot_date'], 'fbm_insight_daily_campaign_date_idx');
            $table->index(['fbm_ad_set_id', 'snapshot_date'], 'fbm_insight_daily_adset_date_idx');
            $table->index(['fbm_ad_id', 'snapshot_date'], 'fbm_insight_daily_ad_date_idx');
            $table->index('freshness_watermark', 'fbm_insight_daily_freshness_idx');
        });
    }
};
