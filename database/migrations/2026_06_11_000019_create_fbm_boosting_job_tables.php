<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fbm_boosting_jobs')) {
            Schema::create('fbm_boosting_jobs', function (Blueprint $table) {
                $table->id();
                $table->uuid('job_uuid')->unique();
                $table->string('job_code', 40)->nullable();
                $table->string('title', 180);
                $table->string('mode', 30)->default('own_store');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('client_name', 180)->nullable();
                $table->char('currency', 3)->default('BDT');
                $table->decimal('planned_budget', 14, 2)->default(0);
                $table->decimal('service_fee', 14, 2)->default(0);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('status', 30)->default('draft');
                $table->string('safe_note', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['mode', 'status'], 'fbm_boost_jobs_mode_status_idx');
                $table->index('customer_id', 'fbm_boost_jobs_customer_idx');
                $table->index(['start_date', 'end_date'], 'fbm_boost_jobs_window_idx');
            });
        }

        if (!Schema::hasTable('fbm_boosting_job_campaigns')) {
            Schema::create('fbm_boosting_job_campaigns', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_boosting_job_id');
                $table->unsignedBigInteger('fbm_campaign_id')->nullable();
                $table->unsignedBigInteger('fbm_ad_set_id')->nullable();
                $table->unsignedBigInteger('fbm_ad_id')->nullable();
                $table->decimal('allocation_percent', 5, 2)->default(100);
                $table->string('safe_note', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('fbm_boosting_job_id', 'fbm_boost_job_campaign_job_idx');
                $table->index('fbm_campaign_id', 'fbm_boost_job_campaign_campaign_idx');
                $table->index('fbm_ad_set_id', 'fbm_boost_job_campaign_adset_idx');
                $table->index('fbm_ad_id', 'fbm_boost_job_campaign_ad_idx');
            });
        }

        if (!Schema::hasTable('fbm_boosting_job_payments')) {
            Schema::create('fbm_boosting_job_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_boosting_job_id');
                $table->date('payment_date');
                $table->string('payment_type', 40)->default('client_payment');
                $table->char('currency', 3)->default('BDT');
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('method', 80)->nullable();
                $table->string('safe_reference', 180)->nullable();
                $table->string('status', 30)->default('confirmed');
                $table->string('safe_note', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['fbm_boosting_job_id', 'payment_date'], 'fbm_boost_pay_job_date_idx');
                $table->index(['payment_type', 'status'], 'fbm_boost_pay_type_status_idx');
            });
        }

        if (!Schema::hasTable('fbm_boosting_job_cost_adjustments')) {
            Schema::create('fbm_boosting_job_cost_adjustments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fbm_boosting_job_id');
                $table->date('cost_date');
                $table->string('cost_type', 60);
                $table->string('label', 160);
                $table->char('currency', 3)->default('BDT');
                $table->decimal('base_amount', 14, 2)->default(0);
                $table->decimal('vat_amount', 14, 2)->default(0);
                $table->decimal('tax_amount', 14, 2)->default(0);
                $table->decimal('service_charge_amount', 14, 2)->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->string('status', 30)->default('approved');
                $table->string('safe_note', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['fbm_boosting_job_id', 'cost_date'], 'fbm_boost_cost_job_date_idx');
                $table->index(['cost_type', 'status'], 'fbm_boost_cost_type_status_idx');
            });
        }
    }

    public function down(): void
    {
        // Boosting job ledgers are finance/audit records and are retained.
    }
};
